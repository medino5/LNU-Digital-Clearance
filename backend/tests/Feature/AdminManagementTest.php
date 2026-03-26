<?php

namespace Tests\Feature;

use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_create_a_program_student_and_scoped_office_account(): void
    {
        // This covers the core admin maintenance flow: the super admin should
        // be able to create routing metadata, provision a student, and create
        // a scoped office account without touching the database manually.
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.programs.store'), [
            'code' => 'BSIS',
            'name' => 'Bachelor of Science in Information Systems',
            'org_name' => 'BITS',
        ])->assertRedirect();

        $program = Program::where('code', 'BSIS')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'student_id_number' => '2400001',
            'name' => 'Jane Systems',
            'program_id' => $program->id,
            'year_level' => 2,
            'password' => 'password',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.office-accounts.store'), [
            'display_name' => 'BITS Academic Organization Treasurer',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
            'year_level' => '',
            'username' => 'bsis.treasurer',
            'password' => 'password',
        ])->assertRedirect();

        $student = Student::where('student_id_number', '2400001')->firstOrFail();
        $officeAccount = OfficeAccount::where('display_name', 'BITS Academic Organization Treasurer')->firstOrFail();
        $designation = OfficeDesignation::where('key', 'bsis-acad-org-treasurer')->firstOrFail();

        $this->assertSame('Jane Systems', $student->user->name);
        $this->assertSame($program->id, $student->program_id);
        $this->assertSame($program->id, $officeAccount->program_id);
        $this->assertSame($program->id, $designation->program_id);
        $this->assertTrue(
            OfficeDesignationAssignment::query()
                ->where('office_designation_id', $designation->id)
                ->where('user_id', $officeAccount->user_id)
                ->where('is_active', true)
                ->exists()
        );
    }

    public function test_activating_a_new_semester_turns_off_the_previous_one(): void
    {
        // This checks the active-semester rule that drives clearance creation:
        // only one semester should remain active after an admin update.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $current = Semester::where('label', '2nd Semester 2024-2025')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'label' => '1st Semester 2025-2026',
            'is_active' => '1',
        ])->assertRedirect();

        $current->refresh();
        $next = Semester::where('label', '1st Semester 2025-2026')->firstOrFail();

        $this->assertFalse($current->is_active);
        $this->assertTrue($next->is_active);
    }

    public function test_program_scoped_office_type_requires_program_scope(): void
    {
        // This guards against invalid admin setup that would break routing:
        // program-scoped office accounts must not be saved without a program.
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('admin.dashboard'))->post(
            route('admin.office-accounts.store'),
            [
                'display_name' => 'Broken Treasurer',
                'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
                'program_id' => '',
                'year_level' => '',
                'username' => 'broken.treasurer',
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHasErrors('program_id');
    }

    public function test_student_role_cannot_open_admin_dashboard(): void
    {
        // This keeps the admin portal isolated from student accounts even if a
        // valid student session exists in the same application.
        $studentUser = User::where('username', '2302314')->firstOrFail();

        $this->actingAs($studentUser)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
