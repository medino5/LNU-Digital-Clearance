<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\Student;
use App\Models\User;
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

    public function test_completed_student_can_download_clearance_pdf_after_all_signatories_approve(): void
    {
        // This is the student-app PDF handoff: before completion the API must
        // block downloads, and after every routed office approves it must
        // return a real PDF response for the mobile Download PDF button.
        Storage::disk('local')->deleteDirectory('clearances');

        $clearance = $this->startClearanceForSeededStudent();

        Sanctum::actingAs($this->seededStudentUser());
        $this->getJson('/api/clearance/current/pdf')
            ->assertStatus(422)
            ->assertJsonPath('message', 'Your clearance is not completed yet.');

        $clearance = $this->approveAllClearanceSteps($clearance);
        Sanctum::actingAs($this->seededStudentUser());

        $this->assertSame(Clearance::STATUS_COMPLETED, $clearance->status);
        $this->assertNotNull($clearance->pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($clearance->pdf_path));

        $response = $this->get('/api/clearance/current/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload($clearance->reference_number . '.pdf');

        $this->assertStringStartsWith(
            '%PDF',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
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
                'confirm_action' => 'approve',
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
                    'confirm_action' => 'approve',
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

        $bsitOfficeUser = User::where('username', 'bsit.treasurer')->firstOrFail();

        $baelOfficeUser = User::where('username', 'bael.treasurer')->firstOrFail();

        $this->actingAs($bsitOfficeUser)
            ->get('/office')
            ->assertOk()
            ->assertSee('John A. Doe');

        $this->actingAs($baelOfficeUser)
            ->get('/office')
            ->assertOk()
            ->assertDontSee('John A. Doe');
    }

    public function test_office_dashboard_shows_empty_state_when_user_has_no_active_designation(): void
    {
        // Ticket 42 replaces the old 404 with a real empty state so office
        // users without an active designation can still reach the portal.
        $officeUser = User::factory()->create([
            'name' => 'Unassigned Office User',
            'username' => 'office.unassigned',
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        OfficeAccount::create([
            'user_id' => $officeUser->id,
            'display_name' => 'Unassigned Office User',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        $this->actingAs($officeUser)
            ->get('/office')
            ->assertOk()
            ->assertSee('No Active Designation Assigned')
            ->assertSee('Please contact the super admin to assign your designation.');
    }

    public function test_office_dashboard_uses_step_snapshot_label_for_designation_cards(): void
    {
        // The dashboard should show the routed step label so the record stays
        // readable even if the live designation title changes later.
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $step = ClearanceStep::query()
            ->where('office_label', 'DIGITS Academic Organization Treasurer')
            ->firstOrFail();

        $step->update([
            'office_label' => 'Snapshot Treasurer Label',
        ]);

        $officeUser = User::where('username', 'bsit.treasurer')->firstOrFail();

        $this->actingAs($officeUser)
            ->get('/office')
            ->assertOk()
            ->assertSee('Designation:')
            ->assertSee('Snapshot Treasurer Label');
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
        $wrongOfficeUser = User::where('username', 'bael.treasurer')->firstOrFail();

        $this->actingAs($wrongOfficeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'approve',
                'confirm_action' => 'approve',
                'remarks' => 'Trying to approve another office step.',
            ])
            ->assertForbidden();

        $step->refresh();
        $this->assertSame(ClearanceStep::STATUS_AWAITING_ACTION, $step->status);
    }

    public function test_office_dashboard_shows_validation_feedback_when_flag_reason_is_missing(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $step = ClearanceStep::query()
            ->where('office_label', 'DIGITS Academic Organization Treasurer')
            ->firstOrFail();
        $officeUser = $step->officeDesignation->activeUsers->first();
        $this->assertNotNull($officeUser);

        $response = $this->actingAs($officeUser)
            ->from(route('office.dashboard'))
            ->post(route('office.steps.process', $step), [
                'action' => 'flag',
                'remarks' => '',
                'step_id' => $step->id,
            ]);

        $response->assertRedirect(route('office.dashboard'));
        $response->assertSessionHasErrorsIn('officeProcess', ['remarks']);

        $this->actingAs($officeUser)
            ->followingRedirects()
            ->from(route('office.dashboard'))
            ->post(route('office.steps.process', $step), [
                'action' => 'flag',
                'remarks' => '',
                'step_id' => $step->id,
            ])
            ->assertOk()
            ->assertSee('Flag reason is required before marking this clearance step as flagged.');
    }

    public function test_processed_approved_step_keeps_view_and_undo_actions_on_office_dashboard(): void
    {
        $clearance = $this->startClearanceForSeededStudent();

        $step = $clearance->steps->firstWhere(
            'office_label',
            'DIGITS Academic Organization Treasurer'
        );
        $officeUser = $step->officeDesignation->activeUsers->first();
        $this->assertNotNull($officeUser);

        $this->actingAs($officeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'approve',
                'confirm_action' => 'approve',
                'remarks' => 'Approved by office dashboard test.',
            ])
            ->assertRedirect();

        $this->actingAs($officeUser)
            ->get('/office')
            ->assertOk()
            ->assertSee('Undo Approval')
            ->assertSee('Approved by office dashboard test.')
            ->assertSee('Processed Note');
    }

    public function test_office_user_can_undo_an_approved_step_and_reopen_the_clearance(): void
    {
        $clearance = $this->startClearanceForSeededStudent();

        $step = $clearance->steps->firstWhere(
            'office_label',
            'DIGITS Academic Organization Treasurer'
        );
        $officeUser = $step->officeDesignation->activeUsers->first();
        $this->assertNotNull($officeUser);

        $this->actingAs($officeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'approve',
                'confirm_action' => 'approve',
                'remarks' => 'Approved before undo.',
            ])
            ->assertRedirect();

        $clearance->refresh();
        $step->refresh();

        $this->assertSame(Clearance::STATUS_IN_PROGRESS, $clearance->status);
        $this->assertSame(ClearanceStep::STATUS_APPROVED, $step->status);

        $this->actingAs($officeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'undo_approval',
                'confirm_action' => 'undo_approval',
            ])
            ->assertRedirect();

        $clearance->refresh();
        $step->refresh();

        $this->assertSame(Clearance::STATUS_IN_PROGRESS, $clearance->status);
        $this->assertSame(ClearanceStep::STATUS_AWAITING_ACTION, $step->status);
        $this->assertNull($step->signed_at);
        $this->assertDatabaseHas('clearance_step_events', [
            'clearance_step_id' => $step->id,
            'actor_user_id' => $officeUser->id,
            'action' => 'undo_approval',
        ]);
    }

    public function test_undoing_approval_on_completed_clearance_clears_completion_artifacts(): void
    {
        $clearance = $this->createCompletedSeededClearance();
        $step = $clearance->steps->first();
        $officeUser = $step->officeDesignation->activeUsers->first();
        $oldPdfPath = $clearance->pdf_path;

        $this->assertNotNull($officeUser);
        $this->assertSame(Clearance::STATUS_COMPLETED, $clearance->status);
        $this->assertNotNull($clearance->reference_number);
        $this->assertNotNull($oldPdfPath);
        $this->assertTrue(Storage::disk('local')->exists($oldPdfPath));

        $this->actingAs($officeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'undo_approval',
                'confirm_action' => 'undo_approval',
            ])
            ->assertRedirect();

        $clearance->refresh();
        $step->refresh();

        $this->assertSame(Clearance::STATUS_IN_PROGRESS, $clearance->status);
        $this->assertSame(ClearanceStep::STATUS_AWAITING_ACTION, $step->status);
        $this->assertNull($clearance->completed_at);
        $this->assertNull($clearance->reference_number);
        $this->assertNull($clearance->pdf_path);
        $this->assertFalse(Storage::disk('local')->exists($oldPdfPath));

        Sanctum::actingAs($clearance->student->user);
        $this->getJson('/api/clearance/current')
            ->assertOk()
            ->assertJsonPath('clearance.status', Clearance::STATUS_IN_PROGRESS)
            ->assertJsonPath('clearance.pdf_available', false);
    }

    public function test_processed_flagged_step_keeps_view_action_and_flag_reason_on_office_dashboard(): void
    {
        $clearance = $this->startClearanceForSeededStudent();

        $step = $clearance->steps->firstWhere(
            'office_label',
            'DIGITS Academic Organization Treasurer'
        );
        $officeUser = $step->officeDesignation->activeUsers->first();
        $this->assertNotNull($officeUser);

        $this->actingAs($officeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'flag',
                'remarks' => 'Missing supporting document.',
                'step_id' => $step->id,
            ])
            ->assertRedirect();

        $this->actingAs($officeUser)
            ->get('/office')
            ->assertOk()
            ->assertSee('Flag Reason')
            ->assertSee('Missing supporting document.')
            ->assertSee('View')
            ->assertSee('Undo Flag');
    }

    public function test_office_user_can_undo_a_flagged_step_and_reopen_the_clearance(): void
    {
        $clearance = $this->startClearanceForSeededStudent();

        $step = $clearance->steps->firstWhere(
            'office_label',
            'DIGITS Academic Organization Treasurer'
        );
        $officeUser = $step->officeDesignation->activeUsers->first();
        $this->assertNotNull($officeUser);

        $this->actingAs($officeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'flag',
                'remarks' => 'Incorrectly flagged by mistake.',
                'step_id' => $step->id,
            ])
            ->assertRedirect();

        $clearance->refresh();
        $step->refresh();

        $this->assertSame(Clearance::STATUS_FLAGGED, $clearance->status);
        $this->assertSame(ClearanceStep::STATUS_FLAGGED, $step->status);

        $this->actingAs($officeUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'undo_flag',
                'confirm_action' => 'undo_flag',
            ])
            ->assertRedirect();

        $clearance->refresh();
        $step->refresh();

        $this->assertSame(Clearance::STATUS_IN_PROGRESS, $clearance->status);
        $this->assertSame(ClearanceStep::STATUS_AWAITING_ACTION, $step->status);
        $this->assertNull($step->signed_at);
        $this->assertNull($step->remarks);
        $this->assertDatabaseHas('clearance_step_events', [
            'clearance_step_id' => $step->id,
            'actor_user_id' => $officeUser->id,
            'action' => 'undo_flag',
        ]);
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
            'display_name' => 'Second DIGITS Treasurer',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
        ]);

        app(\App\Support\OfficeDesignationBackfill::class)
            ->syncOfficeAccount($secondaryOfficeAccount->fresh('program'));

        $step = ClearanceStep::query()
            ->where('office_label', 'DIGITS Academic Organization Treasurer')
            ->firstOrFail();

        $this->actingAs($secondaryHolder)
            ->post(route('office.steps.process', $step), [
                'action' => 'approve',
                'confirm_action' => 'approve',
                'remarks' => 'Approved by secondary holder.',
            ])
            ->assertRedirect();

        $step->refresh();

        $this->assertSame(ClearanceStep::STATUS_APPROVED, $step->status);
        $this->assertSame('Approved by secondary holder.', $step->remarks);
    }

    public function test_student_designation_holder_can_process_matching_step(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $program = \App\Models\Program::where('code', 'BSIT')->firstOrFail();

        $workingStudentUser = User::factory()->create([
            'name' => 'Working Student Holder',
            'username' => 'working.student.holder',
            'role' => User::ROLE_STUDENT,
            'is_student' => true,
            'is_staff' => false,
        ]);

        Student::create([
            'user_id' => $workingStudentUser->id,
            'student_id_number' => '2404321',
            'program_id' => $program->id,
            'year_level' => 3,
        ]);

        \App\Models\OfficeDesignationAssignment::create([
            'office_designation_id' => OfficeDesignation::query()
                ->where('key', 'year-3-treasurer')
                ->value('id'),
            'user_id' => $workingStudentUser->id,
            'assigned_by_user_id' => null,
            'assigned_at' => now(),
            'released_at' => null,
            'is_active' => true,
        ]);

        $step = ClearanceStep::query()
            ->where('office_label', '3rd Year Level Organization Treasurer')
            ->firstOrFail();

        $this->actingAs($workingStudentUser)
            ->post(route('office.steps.process', $step), [
                'action' => 'approve',
                'confirm_action' => 'approve',
                'remarks' => 'Approved by working student holder.',
            ])
            ->assertRedirect();

        $step->refresh();

        $this->assertSame(ClearanceStep::STATUS_APPROVED, $step->status);
        $this->assertSame('Approved by working student holder.', $step->remarks);
    }

    public function test_student_can_initiate_clearance_even_when_required_designation_has_no_active_holder(): void
    {
        // The final restructure should keep designation routing available even
        // if a required designation is temporarily unassigned.
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $designation = OfficeDesignation::query()
            ->where('key', 'bsit-acad-org-treasurer')
            ->firstOrFail();

        \App\Models\OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'released_at' => now(),
            ]);

        Sanctum::actingAs($student->user);
        $response = $this->postJson('/api/clearance');

        $response->assertOk()
            ->assertJsonPath('clearance.counts.total', 5);

        $this->assertDatabaseHas('clearance_steps', [
            'clearance_id' => $response->json('clearance.id'),
            'office_designation_id' => $designation->id,
            'office_label' => 'DIGITS Academic Organization Treasurer',
        ]);
    }

    public function test_unassigned_designation_step_becomes_visible_after_a_later_assignment(): void
    {
        // Steps routed while a designation is unassigned should appear on the
        // office dashboard once a matching holder is assigned later.
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $program = \App\Models\Program::where('code', 'BSIT')->firstOrFail();
        $designation = OfficeDesignation::query()
            ->where('key', 'bsit-acad-org-treasurer')
            ->firstOrFail();

        \App\Models\OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'released_at' => now(),
            ]);

        Sanctum::actingAs($student->user);
        $this->postJson('/api/clearance')->assertOk();

        $newHolder = User::factory()->create([
            'name' => 'Retroactive DIGITS Treasurer',
            'username' => 'digits.retroactive',
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $newOfficeAccount = OfficeAccount::create([
            'user_id' => $newHolder->id,
            'display_name' => 'Retroactive DIGITS Treasurer',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
            'year_level' => null,
        ]);

        app(\App\Support\OfficeDesignationBackfill::class)
            ->syncOfficeAccount($newOfficeAccount->fresh('program'));

        $this->actingAs($newHolder)
            ->get('/office')
            ->assertOk()
            ->assertSee('John A. Doe')
            ->assertSee('DIGITS Academic Organization Treasurer');
    }
}
