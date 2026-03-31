<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeAccount;
use App\Models\User;
use App\Support\OfficeDesignationBackfill;
use App\Support\StudentClearancePayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentClearancePayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_returns_student_snapshot_and_step_summary_for_the_mobile_app(): void
    {
        // This unit test focuses on the payload transformer that feeds the
        // Flutter app, so we can verify the JSON shape without hitting routes.
        // The goal is to prove that one builder call returns the full mobile
        // dashboard payload: student snapshot, semester info, clearance counts,
        // office steps, and the latest event details for flagged items.
        $program = \App\Models\Program::factory()->create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'org_name' => 'DIGITS',
        ]);

        // Create the student and clearance snapshot data exactly the way the
        // mobile app expects to receive it after login.
        $user = User::factory()->namedStudent('John', 'A', 'Doe')->create([
            'username' => '2302314',
        ]);

        $student = \App\Models\Student::factory()->for($user, 'user')->for($program, 'program')->create([
            'student_id_number' => '2302314',
            'year_level' => 3,
        ]);

        $semester = \App\Models\Semester::factory()->active()->create([
            'label' => '2nd Semester 2024-2025',
        ]);

        $clearance = Clearance::create($this->clearanceSnapshotAttributes($student, $semester, [
            'status' => Clearance::STATUS_FLAGGED,
            'reference_number' => 'CLR-1-00001-1234',
        ]));

        // Build one approved program-scoped step so the payload has a
        // completed office entry and a signed event actor.
        $officeUser = User::factory()->office()->create();

        $office = OfficeAccount::factory()
            ->for($officeUser, 'user')
            ->academicOrgTreasurer($program)
            ->create([
            'display_name' => 'Alyssa Mendoza',
        ]);
        $officeDesignation = app(OfficeDesignationBackfill::class)->syncOfficeAccount($office->fresh('program'));

        $approvedStep = $clearance->steps()->create([
            'office_designation_id' => $officeDesignation->id,
            'status' => ClearanceStep::STATUS_APPROVED,
            'office_label' => $officeDesignation->display_name,
            'office_type' => $office->office_type,
            'scope_label' => 'BSIT',
            'signed_at' => now(),
        ]);
        $approvedStep->events()->create([
            'actor_user_id' => $officeUser->id,
            'actor_role' => User::ROLE_OFFICE,
            'action' => 'approved',
        ]);

        // Build one flagged year-level step so we can verify the mobile app's
        // re-submit branch and the last-event remarks payload.
        $yearOfficeUser = User::factory()->office()->create();

        $yearOffice = OfficeAccount::factory()
            ->for($yearOfficeUser, 'user')
            ->yearLevelTreasurer(3)
            ->create([
            'display_name' => 'Carlo Santos',
        ]);
        $yearDesignation = app(OfficeDesignationBackfill::class)->syncOfficeAccount($yearOffice);

        $flaggedStep = $clearance->steps()->create([
            'office_designation_id' => $yearDesignation->id,
            'status' => ClearanceStep::STATUS_FLAGGED,
            'office_label' => '3rd Year Level Organization Treasurer',
            'office_type' => OfficeAccount::TYPE_YEAR_LEVEL_TREASURER,
            'scope_label' => '3rd Year',
            'remarks' => 'Please clear your issue first.',
        ]);
        $flaggedStep->events()->create([
            'actor_user_id' => $yearOfficeUser->id,
            'actor_role' => User::ROLE_OFFICE,
            'action' => 'flagged',
            'remarks' => 'Please clear your issue first.',
        ]);

        // Call the payload builder directly so the test stays narrowly focused
        // on the response shape consumed by Flutter.
        $payload = app(StudentClearancePayloadBuilder::class)->build(
            $student,
            $semester,
            $clearance->fresh(['steps.events', 'steps.officeDesignation'])
        );

        // These assertions protect the fields the mobile UI renders on the
        // overview card, current-clearance summary, and flagged-step actions.
        $this->assertSame('John A. Doe', $payload['student']['name']);
        $this->assertSame('John', $payload['student']['first_name']);
        $this->assertSame('A', $payload['student']['middle_initial']);
        $this->assertSame('Doe', $payload['student']['last_name']);
        $this->assertNull($payload['student']['name_extension']);
        $this->assertSame('BSIT', $payload['student']['program']['code']);
        $this->assertSame('2nd Semester 2024-2025', $payload['active_semester']['label']);
        $this->assertSame(Clearance::STATUS_FLAGGED, $payload['clearance']['status']);
        $this->assertSame(2, $payload['clearance']['counts']['total']);
        $this->assertSame(1, $payload['clearance']['counts']['approved']);
        $this->assertSame(1, $payload['clearance']['counts']['flagged']);
        $this->assertTrue($payload['clearance']['steps'][1]['can_resubmit']);
        $this->assertSame(
            'Please clear your issue first.',
            $payload['clearance']['steps'][1]['last_event']['remarks']
        );
    }
}
