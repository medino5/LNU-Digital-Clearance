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

    public function test_admin_can_create_a_program_student_and_scoped_staff_office_account(): void
    {
        // This covers the core admin maintenance flow: the super admin should
        // be able to create routing metadata, provision a student, and create
        // a scoped staff office account without touching the database manually.
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.programs.store'), [
            'code' => 'BSIS',
            'name' => 'Bachelor of Science in Information Systems',
            'org_name' => 'BITS',
        ])->assertRedirect();

        $program = Program::where('code', 'BSIS')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.students.store'), [
            'student_id_number' => '2400001',
            'first_name' => 'Jane',
            'middle_initial' => '',
            'last_name' => 'Systems',
            'name_extension' => '',
            'program_id' => $program->id,
            'year_level' => 2,
            'password' => 'password',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.office-accounts.store'), [
            'display_name' => 'Bianca Systems',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_ADVISER,
            'program_id' => $program->id,
            'year_level' => '',
            'username' => 'bsis.treasurer',
            'password' => 'password',
        ])->assertRedirect();

        $student = Student::where('student_id_number', '2400001')->firstOrFail();
        $officeAccount = OfficeAccount::whereHas('user', fn ($query) => $query->where('username', 'bsis.treasurer'))->firstOrFail();

        $this->assertSame('Jane Systems', $student->user->name);
        $this->assertSame('Jane', $student->user->first_name);
        $this->assertNull($student->user->middle_initial);
        $this->assertSame('Systems', $student->user->last_name);
        $this->assertNull($student->user->name_extension);
        $this->assertSame('Bianca Systems', $officeAccount->display_name);
        $this->assertSame(OfficeAccount::TYPE_ACAD_ORG_ADVISER, $officeAccount->office_type);
        $this->assertSame($program->id, $student->program_id);
        $this->assertSame($program->id, $officeAccount->program_id);
        $this->assertFalse(
            OfficeDesignationAssignment::query()
                ->where('user_id', $officeAccount->user_id)
                ->where('is_active', true)
                ->exists()
        );
    }

    public function test_program_create_normalizes_code_and_collapses_extra_spaces(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.programs.store'), [
            'code' => ' bs-is ',
            'name' => '  Bachelor   of   Science in   Information Systems  ',
            'org_name' => '  BITS   Circle  ',
        ])->assertRedirect();

        $program = Program::where('code', 'BS-IS')->firstOrFail();

        $this->assertSame('BS-IS', $program->code);
        $this->assertSame('Bachelor of Science in Information Systems', $program->name);
        $this->assertSame('BITS Circle', $program->org_name);
    }

    public function test_program_create_rejects_case_insensitive_duplicate_codes(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('admin.programs.index'))->post(
            route('admin.programs.store'),
            [
                'code' => 'bsit',
                'name' => 'Bachelor of Science in Information Technology Copy',
                'org_name' => 'DIGITS Copy',
            ]
        );

        $response->assertRedirect(route('admin.programs.index'));
        $response->assertSessionHasErrorsIn('programCreate', ['code']);
        $response->assertSessionHasInput('code', 'bsit');
    }

    public function test_program_create_rejects_unsupported_symbols_in_name_and_organization(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('admin.programs.index'))->post(
            route('admin.programs.store'),
            [
                'code' => 'BSTM-2',
                'name' => 'Bachelor of Science in Tourism Management ✨',
                'org_name' => 'Tourism 😊 Circle',
            ]
        );

        $response->assertRedirect(route('admin.programs.index'));
        $response->assertSessionHasErrorsIn('programCreate', ['name', 'org_name']);
        $response->assertSessionHasInput('name', 'Bachelor of Science in Tourism Management ✨');
        $response->assertSessionHasInput('org_name', 'Tourism 😊 Circle');
    }

    public function test_program_update_normalizes_fields_and_keeps_same_code_record_valid(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $program = Program::where('code', 'BAEL')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.programs.update', $program), [
            'code' => ' bael ',
            'name' => '  Bachelor of Arts in English Language (Advanced)  ',
            'org_name' => '  English & Debate   Circle  ',
        ])->assertRedirect();

        $program->refresh();

        $this->assertSame('BAEL', $program->code);
        $this->assertSame('Bachelor of Arts in English Language (Advanced)', $program->name);
        $this->assertSame('English & Debate Circle', $program->org_name);
    }

    public function test_admin_dashboard_student_forms_show_the_7_digit_student_id_constraints(): void
    {
        // This keeps the admin-side guardrails visible in the markup: the
        // student ID inputs should guide the expected 7-digit format and
        // expose the client-side hooks that strip non-digit characters.
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Use the 7-digit format, for example 2302314.')
            ->assertSee('data-student-id-input', false)
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('maxlength="7"', false)
            ->assertSee('pattern="[0-9]{7}"', false);
    }

    public function test_student_create_rejects_non_digit_student_ids_and_keeps_old_input(): void
    {
        // This protects the new format rule behind the UI: even if a request
        // bypasses the browser guard, the server should reject non-digit IDs
        // and return the admin to the same form with their original input.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $program = Program::where('code', 'BSIT')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('admin.students.index'))->post(
            route('admin.students.store'),
            [
                'student_id_number' => '24A0-0😊1',
                'first_name' => 'Jamie',
                'middle_initial' => '',
                'last_name' => 'Digits',
                'name_extension' => '',
                'program_id' => $program->id,
                'year_level' => 2,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHasErrorsIn('studentCreate', ['student_id_number']);
        $response->assertSessionHasInput('student_id_number', '24A0-0😊1');

        $this->assertDatabaseMissing('students', [
            'student_id_number' => '24A0-0😊1',
        ]);
    }

    public function test_student_create_rejects_future_enrollment_year_prefixes(): void
    {
        // This enforces the ticket's year-prefix rule: the first two digits
        // of a student ID cannot point to an enrollment year after today.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $program = Program::where('code', 'BSIT')->firstOrFail();
        $futureStudentId = now()->addYear()->format('y') . '02314';

        $response = $this->actingAs($admin)->from(route('admin.students.index'))->post(
            route('admin.students.store'),
            [
                'student_id_number' => $futureStudentId,
                'first_name' => 'Future',
                'middle_initial' => '',
                'last_name' => 'Enrollee',
                'name_extension' => '',
                'program_id' => $program->id,
                'year_level' => 1,
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('admin.students.index'));
        $response->assertSessionHasErrorsIn('studentCreate', ['student_id_number']);
        $response->assertSessionHasInput('student_id_number', $futureStudentId);
    }

    public function test_updating_student_id_also_updates_the_linked_student_username(): void
    {
        // Mobile login still depends on users.username, so changing the
        // student ID from the admin side must keep both records aligned.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'student_id_number' => '2400123',
            'first_name' => 'John',
            'middle_initial' => 'A',
            'last_name' => 'Doe',
            'name_extension' => '',
            'program_id' => $student->program_id,
            'year_level' => $student->year_level,
            'password' => '',
        ])->assertRedirect();

        $student->refresh()->load('user');

        $this->assertSame('2400123', $student->student_id_number);
        $this->assertSame('2400123', $student->user->username);
    }

    public function test_activating_a_new_semester_turns_off_the_previous_one(): void
    {
        // This checks the active-semester rule that drives clearance creation:
        // only one semester should remain active after an admin update.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $current = Semester::where('label', '2nd Semester 2024-2025')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.semesters.store'), [
            'label' => '1st Semester 2025-2026',
            'academic_year' => '2025-2026',
            'is_active' => '1',
        ])->assertRedirect();

        $current->refresh();
        $next = Semester::where('label', '1st Semester 2025-2026')->firstOrFail();

        $this->assertFalse($current->is_active);
        $this->assertTrue($next->is_active);
        $this->assertSame('2025-2026', $next->academic_year);
    }

    public function test_program_scoped_office_type_requires_program_scope(): void
    {
        // This guards against invalid admin setup that would break routing:
        // program-scoped office accounts must not be saved without a program.
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $response = $this->actingAs($admin)->from(route('admin.office-accounts.index'))->post(
            route('admin.office-accounts.store'),
            [
                'display_name' => 'Broken Holder',
                'office_type' => OfficeAccount::TYPE_ACAD_ORG_ADVISER,
                'program_id' => '',
                'year_level' => '',
                'username' => 'broken.treasurer',
                'password' => 'password',
            ]
        );

        $response->assertRedirect(route('admin.office-accounts.index'));
        $response->assertSessionHasErrorsIn('officeAccountCreate', ['program_id']);
        $response->assertSessionHasInput('display_name', 'Broken Holder');
    }

    public function test_global_office_type_can_be_saved_without_program_or_year_scope(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.office-accounts.store'), [
            'display_name' => 'Lara Perez',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => '',
            'year_level' => '',
            'username' => 'lara.librarian',
            'password' => 'password',
        ])->assertRedirect();

        $officeAccount = OfficeAccount::whereHas('user', fn ($query) => $query->where('username', 'lara.librarian'))->firstOrFail();

        $this->assertSame('Lara Perez', $officeAccount->display_name);
        $this->assertNull($officeAccount->program_id);
        $this->assertNull($officeAccount->year_level);
        $this->assertFalse(
            OfficeDesignationAssignment::query()
                ->where('user_id', $officeAccount->user_id)
                ->where('is_active', true)
                ->exists()
        );
    }

    public function test_admin_dashboard_shows_designation_assignment_section_without_search_filter(): void
    {
        // This keeps ticket 40 aligned with the agreed scope: the assignment
        // controls should be visible on the dashboard, but the extra search UI
        // should not be present.
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.routing.index'))
            ->assertOk()
            ->assertSee('DESIGNATION ASSIGNMENT')
            ->assertSee('Assign Holders')
            ->assertDontSee('Search designation');
    }

    public function test_admin_dashboard_highlights_common_admin_actions_and_clearer_filters(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Common admin actions')
            ->assertSee('Create Student')
            ->assertSee('Create Office Account')
            ->assertSee('Assign Holders')
            ->assertSee('Download Report')
            ->assertSee('Clearances Per Semester')
            ->assertSee('Clearance Status Distribution')
            ->assertSee('No clearance records yet to chart by semester.')
            ->assertSee('No clearance records yet to chart by status.');

        $this->actingAs($admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('Search by name or ID');

        $this->actingAs($admin)
            ->get(route('admin.office-accounts.index'))
            ->assertOk()
            ->assertSee('Search by name, username, or scope');
    }

    public function test_admin_dashboard_renders_real_chart_data_when_clearances_exist(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $student = Student::with('user', 'program')->where('student_id_number', '2302314')->firstOrFail();
        $secondStudent = Student::factory()->create([
            'program_id' => $student->program_id,
            'year_level' => 2,
        ]);
        $thirdStudent = Student::factory()->create([
            'program_id' => $student->program_id,
            'year_level' => 4,
        ]);
        $baseSemester = Semester::where('label', '2nd Semester 2024-2025')->firstOrFail();
        $olderSemester = Semester::factory()->create([
            'label' => '1st Semester 2024-2025',
            'academic_year' => '2024-2025',
        ]);

        \App\Models\Clearance::factory()->forStudentAndSemester($student, $olderSemester)->create([
            'status' => \App\Models\Clearance::STATUS_COMPLETED,
            'completed_at' => now()->subDays(10),
            'reference_number' => 'CLR-OLDER-0001',
        ]);

        \App\Models\Clearance::factory()->forStudentAndSemester($secondStudent, $baseSemester)->create([
            'status' => \App\Models\Clearance::STATUS_IN_PROGRESS,
        ]);

        \App\Models\Clearance::factory()->forStudentAndSemester($thirdStudent, $baseSemester)->create([
            'status' => \App\Models\Clearance::STATUS_FLAGGED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('1st Semester 2024-2025')
            ->assertSee('2nd Semester 2024-2025')
            ->assertSee('In Progress')
            ->assertSee('Flagged')
            ->assertSee('Completed')
            ->assertDontSee('No clearance records yet to chart by semester.')
            ->assertDontSee('No clearance records yet to chart by status.');
    }

    public function test_admin_can_open_the_new_route_based_admin_pages(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.semesters.index'))
            ->assertOk()
            ->assertSee('Semesters')
            ->assertSee('Add or change the current semester');

        $this->actingAs($admin)
            ->get(route('admin.routing.index'))
            ->assertOk()
            ->assertSee('Assign Holders');

        $this->actingAs($admin)
            ->get(route('admin.clearance-history.index'))
            ->assertOk()
            ->assertSee('Completed clearance records by semester and academic year')
            ->assertSee('Download Excel Report');
    }

    public function test_admin_sidebar_highlights_each_current_route_based_page(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $pages = [
            route('admin.dashboard') => 'Dashboard',
            route('admin.programs.index') => 'Programs',
            route('admin.semesters.index') => 'Semesters',
            route('admin.routing.index') => 'Routing',
            route('admin.students.index') => 'Students',
            route('admin.office-accounts.index') => 'Office Accounts',
            route('admin.clearance-history.index') => 'Clearance History',
        ];

        foreach ($pages as $url => $label) {
            $response = $this->actingAs($admin)->get($url);

            $response->assertOk()
                ->assertSee('DIGITAL CLEARANCE')
                ->assertSee('Log Out')
                ->assertSee('href="' . $url . '" class="nav-item active">' . $label, false);

            $this->assertSame(
                1,
                substr_count($response->getContent(), 'class="nav-item active"'),
                "Expected only the {$label} sidebar item to be active."
            );
        }
    }

    public function test_admin_can_reassign_designation_to_an_eligible_office_user(): void
    {
        // Staff office accounts are now a general pool for non-student
        // designations, so a valid staff account can hold a non-student
        // designation even if its own office type is different.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $designation = OfficeDesignation::where('key', 'bsit-acad-org-adviser')->firstOrFail();
        $currentAssignment = OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->firstOrFail();

        $replacementUser = User::create([
            'name' => 'Alyssa Mendoza',
            'username' => 'digits.alt.treasurer',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        OfficeAccount::create([
            'user_id' => $replacementUser->id,
            'display_name' => 'Alyssa Mendoza',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.routing.index'))
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => $replacementUser->id,
            ]);

        $response->assertRedirect(route('admin.routing.index'));
        $response->assertSessionHas('success', 'Designation assignment updated successfully.');

        $this->assertDatabaseHas('office_designation_assignments', [
            'office_designation_id' => $designation->id,
            'user_id' => $replacementUser->id,
            'assigned_by_user_id' => $admin->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('office_designation_assignments', [
            'id' => $currentAssignment->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_assign_matching_student_to_a_student_led_designation(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $designation = OfficeDesignation::where('key', 'year-3-treasurer')->firstOrFail();
        $currentAssignment = OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('admin.routing.index'))
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => $student->user_id,
            ]);

        $response->assertRedirect(route('admin.routing.index'));
        $response->assertSessionHas('success', 'Designation assignment updated successfully.');

        $this->assertDatabaseHas('office_designation_assignments', [
            'office_designation_id' => $designation->id,
            'user_id' => $student->user_id,
            'assigned_by_user_id' => $admin->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('office_designation_assignments', [
            'id' => $currentAssignment->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_cannot_assign_an_ineligible_office_user_to_a_designation(): void
    {
        // Student-led designations stay student-only even after staff office
        // accounts became a reusable pool for non-student designations.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $designation = OfficeDesignation::where('key', 'bsit-acad-org-treasurer')->firstOrFail();
        $currentAssignment = OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->firstOrFail();

        $ineligibleUser = User::create([
            'name' => 'Liza Perez',
            'username' => 'librarian.alt',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        OfficeAccount::create([
            'user_id' => $ineligibleUser->id,
            'display_name' => 'Liza Perez',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.routing.index'))
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => $ineligibleUser->id,
            ]);

        $response->assertRedirect(route('admin.routing.index'));
        $response->assertSessionHas('error', 'The selected user is not eligible for this designation.');

        $this->assertDatabaseMissing('office_designation_assignments', [
            'office_designation_id' => $designation->id,
            'user_id' => $ineligibleUser->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('office_designation_assignments', [
            'id' => $currentAssignment->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_assign_student_to_a_non_student_designation(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $designation = OfficeDesignation::where('key', 'college-librarian')->firstOrFail();
        $currentAssignment = OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('admin.routing.index'))
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => $student->user_id,
            ]);

        $response->assertRedirect(route('admin.routing.index'));
        $response->assertSessionHas('error', 'The selected user is not eligible for this designation.');

        $this->assertDatabaseMissing('office_designation_assignments', [
            'office_designation_id' => $designation->id,
            'user_id' => $student->user_id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('office_designation_assignments', [
            'id' => $currentAssignment->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_cannot_assign_student_to_a_different_program_designation(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $designation = OfficeDesignation::where('key', 'bsentrep-acad-org-treasurer')->firstOrFail();
        $currentAssignment = OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('admin.routing.index'))
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => $student->user_id,
            ]);

        $response->assertRedirect(route('admin.routing.index'));
        $response->assertSessionHas('error', 'The selected user is not eligible for this designation.');

        $this->assertDatabaseMissing('office_designation_assignments', [
            'office_designation_id' => $designation->id,
            'user_id' => $student->user_id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('office_designation_assignments', [
            'id' => $currentAssignment->id,
            'is_active' => true,
        ]);
    }

    public function test_reassigning_the_current_designation_holder_does_not_create_duplicate_history(): void
    {
        // The current holder is preselected in the UI, so re-saving without a
        // change should stay a no-op instead of creating extra history rows.
        $admin = User::where('username', 'mis.admin')->firstOrFail();
        $designation = OfficeDesignation::where('key', 'bsit-acad-org-adviser')->firstOrFail();
        $currentAssignment = OfficeDesignationAssignment::query()
            ->where('office_designation_id', $designation->id)
            ->where('is_active', true)
            ->firstOrFail();

        $response = $this->actingAs($admin)
            ->from(route('admin.routing.index'))
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => $currentAssignment->user_id,
            ]);

        $response->assertRedirect(route('admin.routing.index'));
        $response->assertSessionHas('info', 'Designation assignment is already up to date.');

        $this->assertSame(
            1,
            OfficeDesignationAssignment::query()
                ->where('office_designation_id', $designation->id)
                ->where('user_id', $currentAssignment->user_id)
                ->count()
        );

        $this->assertDatabaseHas('office_designation_assignments', [
            'id' => $currentAssignment->id,
            'is_active' => true,
        ]);
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
