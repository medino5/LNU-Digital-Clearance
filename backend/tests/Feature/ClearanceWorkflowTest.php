<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ClearanceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_clearance_creation_is_routed_to_five_required_offices(): void
    {
        // This is the core workflow entry test: when a student starts a
        // clearance, the system should create exactly the five offices defined
        // by the rehauled routing rules and reuse the same record on retry.
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);

        $response = $this->postJson('/api/clearance');

        $response->assertOk();
        $response->assertJsonPath('clearance.counts.total', 5);

        $officeLabels = collect($response->json('clearance.steps'))
            ->pluck('office_label')
            ->all();

        $this->assertEqualsCanonicalizing([
            'DIGITS Academic Organization Treasurer',
            'DIGITS Academic Organization Adviser',
            '3rd Year Level Organization Treasurer',
            'College Chief Librarian',
            'Vice President for Student Development',
        ], $officeLabels);

        $secondResponse = $this->postJson('/api/clearance');

        $secondResponse->assertOk();
        $this->assertSame(
            $response->json('clearance.id'),
            $secondResponse->json('clearance.id'),
        );
        $this->assertDatabaseCount('clearances', 1);
        $this->assertDatabaseCount('clearance_steps', 5);
        $this->assertDatabaseHas('clearance_steps', [
            'clearance_id' => $response->json('clearance.id'),
            'office_designation_id' => OfficeDesignation::query()
                ->where('key', 'bsit-acad-org-treasurer')
                ->value('id'),
        ]);
    }

    public function test_clearance_snapshots_do_not_change_after_student_profile_edits(): void
    {
        // This protects historical integrity. A clearance should keep the
        // original program and year snapshot even if the student's profile
        // changes later in the admin portal.
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);

        $this->postJson('/api/clearance')->assertOk();

        $clearance = Clearance::firstOrFail();
        $student->update([
            'year_level' => 1,
            'program_id' => \App\Models\Program::where('code', 'BAEL')->value('id'),
        ]);

        $clearance->refresh();

        $this->assertSame('BSIT', $clearance->program_code);
        $this->assertSame(3, $clearance->year_level);
    }

    public function test_flagged_step_can_be_resubmitted_without_resetting_other_approved_steps(): void
    {
        // This verifies the most important branch in the new workflow: one
        // flagged office can be resubmitted without erasing approvals that
        // other offices already completed.
        Storage::disk('local')->deleteDirectory('clearances');

        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $clearance = Clearance::with('steps.officeDesignation.activeUsers')->firstOrFail();

        $flaggedStep = $clearance->steps->firstWhere(
            'office_label',
            '3rd Year Level Organization Treasurer'
        );
        $approvedStep = $clearance->steps->firstWhere(
            'office_label',
            'DIGITS Academic Organization Treasurer'
        );
        $approvedOfficeUser = $approvedStep->officeDesignation->activeUsers->first();
        $this->assertNotNull($approvedOfficeUser);

        $this->actingAs($approvedOfficeUser)
            ->post(route('office.steps.process', $approvedStep), [
                'action' => 'approve',
                'remarks' => 'Approved by test office.',
            ])
            ->assertRedirect();

        $flaggedOfficeUser = $flaggedStep->officeDesignation->activeUsers->first();
        $this->assertNotNull($flaggedOfficeUser);

        $this->actingAs($flaggedOfficeUser)
            ->post(route('office.steps.process', $flaggedStep), [
                'action' => 'flag',
                'remarks' => 'Please resolve your concern.',
            ])
            ->assertRedirect();

        $clearance->refresh();
        $approvedStep->refresh();
        $flaggedStep->refresh();

        $this->assertSame(Clearance::STATUS_FLAGGED, $clearance->status);
        $this->assertSame(ClearanceStep::STATUS_APPROVED, $approvedStep->status);
        $this->assertSame(ClearanceStep::STATUS_FLAGGED, $flaggedStep->status);

        Sanctum::actingAs($student->user);

        $this->postJson("/api/clearance/steps/{$flaggedStep->id}/resubmit")
            ->assertOk()
            ->assertJsonPath('clearance.status', Clearance::STATUS_IN_PROGRESS);

        $approvedStep->refresh();
        $flaggedStep->refresh();

        $this->assertSame(ClearanceStep::STATUS_APPROVED, $approvedStep->status);
        $this->assertSame(ClearanceStep::STATUS_AWAITING_ACTION, $flaggedStep->status);

        $clearance->refresh();
        $clearance->load('steps.officeDesignation.activeUsers');

        foreach ($clearance->steps as $step) {
            $officeUser = $step->officeDesignation->activeUsers->first();
            $this->assertNotNull($officeUser);

            $this->actingAs($officeUser)
                ->post(route('office.steps.process', $step), [
                    'action' => 'approve',
                    'remarks' => 'Approved.',
                ])
                ->assertRedirect();
        }

        $clearance->refresh();

        $this->assertSame(Clearance::STATUS_COMPLETED, $clearance->status);
        $this->assertNotNull($clearance->reference_number);
        $this->assertNotNull($clearance->pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($clearance->pdf_path));

        Sanctum::actingAs($student->user);

        $this->get('/api/clearance/current/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_office_dashboard_only_shows_students_routed_to_that_office(): void
    {
        // This keeps office scope isolation intact: an office account should
        // see only the students routed to that exact office and scope.
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $bsitOfficeUser = OfficeAccount::where(
            'display_name',
            'DIGITS Academic Organization Treasurer'
        )->firstOrFail()->user;

        $baelOfficeUser = OfficeAccount::where(
            'display_name',
            'English Circle Academic Organization Treasurer'
        )->firstOrFail()->user;

        $this->actingAs($bsitOfficeUser)
            ->get('/office')
            ->assertOk()
            ->assertSee('John A. Doe');

        $this->actingAs($baelOfficeUser)
            ->get('/office')
            ->assertOk()
            ->assertDontSee('John A. Doe');
    }

    public function test_office_user_cannot_process_a_step_owned_by_a_different_office(): void
    {
        // This ensures step processing stays locked to the assigned office
        // account, even if another valid office user tries to post directly to
        // the process route.
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $step = ClearanceStep::query()->firstOrFail();
        $wrongOfficeUser = OfficeAccount::where(
            'display_name',
            'English Circle Academic Organization Treasurer'
        )->firstOrFail()->user;

        $this->actingAs($wrongOfficeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'approve',
                'remarks' => 'Trying to approve another office step.',
            ])
            ->assertForbidden();

        $step->refresh();
        $this->assertSame(ClearanceStep::STATUS_AWAITING_ACTION, $step->status);
    }

    public function test_any_active_holder_of_a_designation_can_process_the_step(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $program = \App\Models\Program::where('code', 'BSIT')->firstOrFail();

        $secondaryHolder = \App\Models\User::factory()->create([
            'name' => 'Second DIGITS Treasurer',
            'username' => 'digits.second',
            'role' => \App\Models\User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $secondaryOfficeAccount = OfficeAccount::create([
            'user_id' => $secondaryHolder->id,
            'display_name' => 'DIGITS Academic Organization Treasurer',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
        ]);

        app(\App\Support\OfficeDesignationBackfill::class)
            ->syncOfficeAccount($secondaryOfficeAccount->fresh('program'));

        $step = ClearanceStep::query()
            ->where('office_label', 'DIGITS Academic Organization Treasurer')
            ->firstOrFail();

        $this->assertNotSame($secondaryOfficeAccount->id, $step->office_account_id);

        $this->actingAs($secondaryHolder)
            ->post(route('office.steps.process', $step), [
                'action' => 'approve',
                'remarks' => 'Approved by secondary holder.',
            ])
            ->assertRedirect();

        $step->refresh();

        $this->assertSame(ClearanceStep::STATUS_APPROVED, $step->status);
        $this->assertSame('Approved by secondary holder.', $step->remarks);
    }
}
