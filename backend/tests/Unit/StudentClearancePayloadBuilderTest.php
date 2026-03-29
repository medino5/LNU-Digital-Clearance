<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeAccount;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Support\OfficeDesignationBackfill;
use App\Support\StudentClearancePayloadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentClearancePayloadBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_returns_student_snapshot_and_step_summary_for_the_mobile_app(): void
    {
        // This unit test focuses on the payload transformer that feeds the
        // Flutter app, so we can verify the JSON shape without hitting routes.
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'org_name' => 'DIGITS',
        ]);

        $user = User::factory()->create([
            'name' => 'John A. Doe',
            'first_name' => 'John',
            'middle_initial' => 'A',
            'last_name' => 'Doe',
            'name_extension' => null,
            'username' => '2302314',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'is_student' => true,
            'is_staff' => false,
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'student_id_number' => '2302314',
            'program_id' => $program->id,
            'year_level' => 3,
        ]);

        $semester = Semester::create([
            'label' => '2nd Semester 2024-2025',
            'is_active' => true,
        ]);

        $clearance = Clearance::create([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'status' => Clearance::STATUS_FLAGGED,
            'reference_number' => 'CLR-1-00001-1234',
            'student_name' => 'John A. Doe',
            'student_id_number' => '2302314',
            'year_level' => 3,
            'program_code' => 'BSIT',
            'program_name' => 'Bachelor of Science in Information Technology',
            'organization_name' => 'DIGITS',
            'semester_label' => $semester->label,
        ]);

        $officeUser = User::factory()->create([
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $office = OfficeAccount::create([
            'user_id' => $officeUser->id,
            'display_name' => 'DIGITS Academic Organization Treasurer',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
            'year_level' => null,
        ]);
        $officeDesignation = app(OfficeDesignationBackfill::class)->syncOfficeAccount($office->fresh('program'));

        $approvedStep = $clearance->steps()->create([
            'office_designation_id' => $officeDesignation->id,
            'status' => ClearanceStep::STATUS_APPROVED,
            'office_label' => $office->display_name,
            'office_type' => $office->office_type,
            'scope_label' => 'BSIT',
            'signed_at' => now(),
        ]);
        $approvedStep->events()->create([
            'actor_user_id' => $officeUser->id,
            'actor_role' => User::ROLE_OFFICE,
            'action' => 'approved',
        ]);

        $yearOfficeUser = User::factory()->create([
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $yearOffice = OfficeAccount::create([
            'user_id' => $yearOfficeUser->id,
            'display_name' => '3rd Year Level Organization Treasurer',
            'office_type' => OfficeAccount::TYPE_YEAR_LEVEL_TREASURER,
            'program_id' => null,
            'year_level' => 3,
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

        $payload = app(StudentClearancePayloadBuilder::class)->build(
            $student,
            $semester,
            $clearance->fresh(['steps.events', 'steps.officeDesignation'])
        );

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
