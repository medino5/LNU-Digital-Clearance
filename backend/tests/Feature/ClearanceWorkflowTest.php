<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\ClearanceStep;
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
    }

    public function test_clearance_snapshots_do_not_change_after_student_profile_edits(): void
    {
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
        Storage::disk('local')->deleteDirectory('clearances');

        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $clearance = Clearance::with('steps.officeAccount.user')->firstOrFail();

        $flaggedStep = $clearance->steps->firstWhere(
            'office_label',
            '3rd Year Level Organization Treasurer'
        );
        $approvedStep = $clearance->steps->firstWhere(
            'office_label',
            'DIGITS Academic Organization Treasurer'
        );

        $this->actingAs($approvedStep->officeAccount->user)
            ->post(route('office.steps.process', $approvedStep), [
                'action' => 'approve',
                'remarks' => 'Approved by test office.',
            ])
            ->assertRedirect();

        $this->actingAs($flaggedStep->officeAccount->user)
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
        $clearance->load('steps.officeAccount.user');

        foreach ($clearance->steps as $step) {
            $this->actingAs($step->officeAccount->user)
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
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $bsitOfficeUser = \App\Models\OfficeAccount::where(
            'display_name',
            'DIGITS Academic Organization Treasurer'
        )->firstOrFail()->user;

        $baelOfficeUser = \App\Models\OfficeAccount::where(
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
}
