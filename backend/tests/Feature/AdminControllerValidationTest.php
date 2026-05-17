<?php

namespace Tests\Feature;

use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentRegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_program_index_lists_programs_ordered_by_code(): void
    {
        Program::factory()->create(['code' => 'BSTM', 'name' => 'Tourism', 'org_name' => 'Tourism Org']);
        Program::factory()->create(['code' => 'BAEL', 'name' => 'English', 'org_name' => 'English Org']);

        $this->actingAs($this->admin)
            ->get(route('admin.programs.index'))
            ->assertOk()
            ->assertSeeInOrder(['BAEL', 'BSTM']);
    }

    public function test_program_create_rejects_missing_organization_name(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.programs.index'))
            ->post(route('admin.programs.store'), [
                'code' => 'BSBIO',
                'name' => 'Bachelor of Science in Biology',
                'org_name' => '',
            ])
            ->assertRedirect(route('admin.programs.index'))
            ->assertSessionHasErrors(['org_name'], null, 'programCreate');

        $this->assertDatabaseMissing('programs', ['code' => 'BSBIO']);
    }

    public function test_program_create_rejects_overlong_code_name_and_organization(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.programs.index'))
            ->post(route('admin.programs.store'), [
                'code' => str_repeat('A', 16),
                'name' => str_repeat('B', 121),
                'org_name' => str_repeat('C', 121),
            ])
            ->assertRedirect(route('admin.programs.index'))
            ->assertSessionHasErrors(['code', 'name', 'org_name'], null, 'programCreate');
    }

    public function test_semester_create_rejects_invalid_academic_year_format(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.semesters.store'), [
                'label' => '1st Semester 2026',
                'academic_year' => '2026',
            ])
            ->assertRedirect(route('admin.semesters.index'))
            ->assertSessionHasErrors(['academic_year'], null, 'semesterCreate');
    }

    public function test_semester_create_rejects_overlong_labels(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.semesters.store'), [
                'label' => str_repeat('A', 81),
                'academic_year' => '2026-2027',
            ])
            ->assertRedirect(route('admin.semesters.index'))
            ->assertSessionHasErrors(['label'], null, 'semesterCreate');
    }

    public function test_semester_update_activation_deactivates_other_semesters(): void
    {
        $old = Semester::factory()->active()->create(['label' => '1st Semester 2025-2026']);
        $new = Semester::factory()->create(['label' => '2nd Semester 2025-2026']);

        $this->actingAs($this->admin)
            ->put(route('admin.semesters.update', $new), [
                'label' => '2nd Semester 2025-2026',
                'academic_year' => '2025-2026',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.semesters.index'));

        $this->assertFalse($old->fresh()->is_active);
        $this->assertTrue($new->fresh()->is_active);
    }

    public function test_students_page_searches_by_first_name(): void
    {
        Student::factory()
            ->for(User::factory()->namedStudent('Althea', null, 'Santos'), 'user')
            ->create();
        Student::factory()
            ->for(User::factory()->namedStudent('Bryan', null, 'Reyes'), 'user')
            ->create();

        $this->actingAs($this->admin)
            ->get(route('admin.students.index', ['student_search' => 'Althea']))
            ->assertOk()
            ->assertSee('Althea')
            ->assertDontSee('Bryan');
    }

    public function test_student_search_rejects_overlong_input_before_querying(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.students.index'))
            ->get(route('admin.students.index', [
                'student_search' => str_repeat('A', 81),
            ]))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors('student_search');
    }

    public function test_students_page_orders_by_student_number(): void
    {
        $program = Program::factory()->create();

        Student::factory()->create([
            'program_id' => $program->id,
            'student_id_number' => '2302316',
        ]);
        Student::factory()->create([
            'program_id' => $program->id,
            'student_id_number' => '2302314',
        ]);
        Student::factory()->create([
            'program_id' => $program->id,
            'student_id_number' => '2302315',
        ]);

        $content = $this->actingAs($this->admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            strpos($content, '2302314') < strpos($content, '2302315')
            && strpos($content, '2302315') < strpos($content, '2302316')
        );
    }

    public function test_students_page_searches_by_last_name(): void
    {
        Student::factory()
            ->for(User::factory()->namedStudent('Nico', null, 'Villanueva'), 'user')
            ->create();
        Student::factory()
            ->for(User::factory()->namedStudent('Mara', null, 'Lopez'), 'user')
            ->create();

        $this->actingAs($this->admin)
            ->get(route('admin.students.index', ['student_search' => 'Villanueva']))
            ->assertOk()
            ->assertSee('Villanueva')
            ->assertDontSee('Lopez');
    }

    public function test_students_page_filters_by_program(): void
    {
        $bsit = Program::factory()->create(['code' => 'BSIT']);
        $bael = Program::factory()->create(['code' => 'BAEL']);
        Student::factory()->create(['program_id' => $bsit->id, 'student_id_number' => '2400001']);
        Student::factory()->create(['program_id' => $bael->id, 'student_id_number' => '2400002']);

        $this->actingAs($this->admin)
            ->get(route('admin.students.index', ['student_program' => $bsit->id]))
            ->assertOk()
            ->assertSee('2400001')
            ->assertDontSee('2400002');
    }

    public function test_students_page_filters_by_year_level(): void
    {
        Student::factory()->create(['year_level' => 1, 'student_id_number' => '2400101']);
        Student::factory()->create(['year_level' => 4, 'student_id_number' => '2400401']);

        $this->actingAs($this->admin)
            ->get(route('admin.students.index', ['student_year_level' => 4]))
            ->assertOk()
            ->assertSee('2400401')
            ->assertDontSee('2400101');
    }

    public function test_student_create_rejects_emoji_in_name_fields(): void
    {
        $program = Program::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.students.store'), [
                'student_id_number' => '2400999',
                'first_name' => 'Ana😀',
                'middle_initial' => 'C',
                'last_name' => 'Santos',
                'program_id' => $program->id,
                'year_level' => 1,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors(['first_name'], null, 'studentCreate');
    }

    public function test_student_create_accepts_lowercase_and_enye_names(): void
    {
        $program = Program::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.students.store'), [
                'student_id_number' => '2400777',
                'first_name' => 'niña',
                'middle_initial' => 'ñ',
                'last_name' => 'dela cruz',
                'program_id' => $program->id,
                'year_level' => 2,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.students.index'));

        $student = Student::where('student_id_number', '2400777')->firstOrFail();

        $this->assertSame('niña Ñ. dela cruz', $student->user->formattedName());
    }

    public function test_student_create_rejects_overlong_names_and_passwords(): void
    {
        $program = Program::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.students.store'), [
                'student_id_number' => '2400888',
                'first_name' => str_repeat('A', 61),
                'middle_initial' => 'C',
                'last_name' => str_repeat('B', 61),
                'program_id' => $program->id,
                'year_level' => 1,
                'password' => str_repeat('p', 73),
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors(['first_name', 'last_name', 'password'], null, 'studentCreate');
    }

    public function test_student_update_without_password_preserves_existing_password(): void
    {
        $program = Program::factory()->create();
        $student = Student::factory()->create([
            'program_id' => $program->id,
            'student_id_number' => '2401234',
        ]);
        $oldHash = $student->user->password;

        $this->actingAs($this->admin)
            ->put(route('admin.students.update', $student), [
                'student_id_number' => $student->student_id_number,
                'first_name' => 'Updated',
                'middle_initial' => 'A',
                'last_name' => 'Student',
                'program_id' => $program->id,
                'year_level' => 2,
                'password' => '',
            ])
            ->assertRedirect(route('admin.students.index'));

        $this->assertSame($oldHash, $student->user->fresh()->password);
        $this->assertSame('Updated A. Student', $student->user->fresh()->formattedName());
    }

    public function test_admin_can_view_student_profile_with_clearance_progress(): void
    {
        $student = Student::factory()
            ->for(User::factory()->namedStudent('Niña', 'Ñ', 'Santos'), 'user')
            ->create();

        $signer = User::factory()->office()->create(['name' => 'Library Signer']);

        $clearance = \App\Models\Clearance::factory()
            ->forStudentAndSemester($student, Semester::factory()->create())
            ->create(['status' => \App\Models\Clearance::STATUS_IN_PROGRESS]);
        $step = \App\Models\ClearanceStep::factory()
            ->approved('Verified by library.')
            ->create([
                'clearance_id' => $clearance->id,
                'office_label' => 'College Chief Librarian',
            ]);
        \App\Models\ClearanceStepEvent::factory()->create([
            'clearance_step_id' => $step->id,
            'actor_user_id' => $signer->id,
            'actor_role' => User::ROLE_OFFICE,
            'action' => 'approved',
            'remarks' => 'Verified by library.',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.students.show', $student))
            ->assertOk()
            ->assertSee('Student Profile')
            ->assertSee('Niña Ñ. Santos')
            ->assertSee('Current Progress')
            ->assertSee('View signers')
            ->assertSee('College Chief Librarian')
            ->assertSee('Library Signer')
            ->assertSee('Verified by library.');
    }

    public function test_office_user_can_open_profile_for_routed_student_only(): void
    {
        $student = Student::factory()->create();
        $otherStudent = Student::factory()->create();
        $officeAccount = OfficeAccount::factory()->librarian()->create();
        $designation = OfficeDesignation::factory()->librarian()->create();
        OfficeDesignationAssignment::factory()->create([
            'office_designation_id' => $designation->id,
            'user_id' => $officeAccount->user_id,
        ]);

        $clearance = \App\Models\Clearance::factory()->create(['student_id' => $student->id]);
        \App\Models\ClearanceStep::factory()->create([
            'clearance_id' => $clearance->id,
            'office_designation_id' => $designation->id,
            'office_label' => $designation->display_name,
        ]);

        $this->actingAs($officeAccount->user)
            ->get(route('office.students.show', $student))
            ->assertOk()
            ->assertSee($student->student_id_number);

        $this->actingAs($officeAccount->user)
            ->get(route('office.students.show', $otherStudent))
            ->assertForbidden();
    }

    public function test_admin_can_delete_student_account_with_hard_confirmation(): void
    {
        $student = Student::factory()->create(['student_id_number' => '2400666']);
        $userId = $student->user_id;

        $this->actingAs($this->admin)
            ->delete(route('admin.students.destroy', $student), [
                'delete_confirmation' => 'DELETE 2400666',
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('success', 'Student account deleted successfully.');

        $this->assertDatabaseMissing('students', ['student_id_number' => '2400666']);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_student_delete_rejects_missing_hard_confirmation(): void
    {
        $student = Student::factory()->create(['student_id_number' => '2400667']);

        $this->actingAs($this->admin)
            ->delete(route('admin.students.destroy', $student), [
                'delete_confirmation' => 'delete 2400667',
            ])
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHasErrors(['delete_confirmation'], null, 'studentDelete');

        $this->assertDatabaseHas('students', ['student_id_number' => '2400667']);
    }

    public function test_office_accounts_page_filters_by_office_type(): void
    {
        OfficeAccount::factory()->vpsd()->create(['display_name' => 'VPSD Office']);
        OfficeAccount::factory()->librarian()->create(['display_name' => 'Library Office']);

        $this->actingAs($this->admin)
            ->get(route('admin.office-accounts.index', ['office_type' => OfficeAccount::TYPE_VPSD]))
            ->assertOk()
            ->assertSee('VPSD Office')
            ->assertDontSee('Library Office');
    }

    public function test_office_account_search_rejects_overlong_input_before_querying(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.office-accounts.index'))
            ->get(route('admin.office-accounts.index', [
                'office_search' => str_repeat('A', 101),
            ]))
            ->assertRedirect(route('admin.office-accounts.index'))
            ->assertSessionHasErrors('office_search');
    }

    public function test_office_accounts_page_filters_university_wide_accounts(): void
    {
        $program = Program::factory()->create(['code' => 'BSIT']);
        OfficeAccount::factory()->vpsd()->create(['display_name' => 'Global VPSD']);
        OfficeAccount::factory()->academicOrgAdviser($program)->create(['display_name' => 'Program Adviser']);

        $this->actingAs($this->admin)
            ->get(route('admin.office-accounts.index', ['office_program' => 'university']))
            ->assertOk()
            ->assertSee('Global VPSD')
            ->assertDontSee('Program Adviser');
    }

    public function test_office_account_create_trims_name_and_clears_global_scope_fields(): void
    {
        $program = Program::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.office-accounts.store'), [
                'display_name' => '  VPSD Staff  ',
                'office_type' => OfficeAccount::TYPE_VPSD,
                'program_id' => $program->id,
                'year_level' => 3,
                'username' => 'vpsd.staff',
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.office-accounts.index'));

        $this->assertDatabaseHas('office_accounts', [
            'display_name' => 'VPSD Staff',
            'office_type' => OfficeAccount::TYPE_VPSD,
            'program_id' => null,
            'year_level' => null,
        ]);
    }

    public function test_office_account_create_rejects_overlong_identity_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.office-accounts.store'), [
                'display_name' => str_repeat('A', 101),
                'office_type' => OfficeAccount::TYPE_LIBRARIAN,
                'program_id' => '',
                'year_level' => '',
                'username' => str_repeat('u', 61),
                'password' => str_repeat('p', 73),
            ])
            ->assertRedirect(route('admin.office-accounts.index'))
            ->assertSessionHasErrors(['display_name', 'username', 'password'], null, 'officeAccountCreate');
    }

    public function test_office_account_update_without_password_preserves_existing_password(): void
    {
        $officeAccount = OfficeAccount::factory()->librarian()->create();
        $oldHash = $officeAccount->user->password;

        $this->actingAs($this->admin)
            ->put(route('admin.office-accounts.update', $officeAccount), [
                'display_name' => 'Updated Library Staff',
                'office_type' => OfficeAccount::TYPE_LIBRARIAN,
                'username' => $officeAccount->user->username,
                'password' => '',
            ])
            ->assertRedirect(route('admin.office-accounts.index'));

        $this->assertSame($oldHash, $officeAccount->user->fresh()->password);
        $this->assertSame('Updated Library Staff', $officeAccount->fresh()->display_name);
    }

    public function test_designation_assignment_requires_a_user_selection(): void
    {
        $designation = OfficeDesignation::factory()->librarian()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => '',
            ])
            ->assertRedirect(route('admin.routing.index'))
            ->assertSessionHasErrors(['user_id'], null, 'designationAssignment');
    }

    public function test_designation_assignment_to_same_user_does_not_duplicate_history(): void
    {
        $designation = OfficeDesignation::factory()->librarian()->create();
        $officeAccount = OfficeAccount::factory()->librarian()->create();
        OfficeDesignationAssignment::factory()->create([
            'office_designation_id' => $designation->id,
            'user_id' => $officeAccount->user_id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.office-designations.assignment.update', $designation), [
                'user_id' => $officeAccount->user_id,
            ])
            ->assertRedirect(route('admin.routing.index'))
            ->assertSessionHas('info', 'Designation assignment is already up to date.');

        $this->assertDatabaseCount('office_designation_assignments', 1);
    }

    public function test_routing_page_scopes_candidate_dropdowns_to_matching_holder_types(): void
    {
        $bsit = Program::factory()->create(['code' => 'BSIT', 'org_name' => 'DIGITS']);
        $bael = Program::factory()->create(['code' => 'BAEL', 'org_name' => 'ELITES']);

        $programDesignation = OfficeDesignation::factory()->academicOrgTreasurer($bsit)->create();
        $yearDesignation = OfficeDesignation::factory()->yearLevelTreasurer(4)->create();
        $staffDesignation = OfficeDesignation::factory()->librarian()->create();

        Student::factory()
            ->for(User::factory()->namedStudent('Paolo', null, 'Programmatch'), 'user')
            ->create(['program_id' => $bsit->id, 'year_level' => 1]);

        Student::factory()
            ->for(User::factory()->namedStudent('Mika', null, 'Yearmatch'), 'user')
            ->create(['program_id' => $bael->id, 'year_level' => 4]);

        Student::factory()
            ->for(User::factory()->namedStudent('Rico', null, 'Noteligible'), 'user')
            ->create(['program_id' => $bael->id, 'year_level' => 2]);

        OfficeAccount::factory()
            ->librarian()
            ->create(['display_name' => 'Library Staff Holder']);

        $this->actingAs($this->admin)
            ->get(route('admin.routing.index'))
            ->assertOk()
            ->assertSee('Candidate list loads only when needed to keep this page fast.')
            ->assertDontSee('Noteligible');

        $this->actingAs($this->admin)
            ->getJson(route('admin.office-designations.eligible-users', [
                'officeDesignation' => $programDesignation,
                'search' => 'Paolo',
            ]))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Student - Paolo Programmatch'])
            ->assertJsonMissing(['label' => 'Student - Rico Noteligible']);

        $this->actingAs($this->admin)
            ->getJson(route('admin.office-designations.eligible-users', [
                'officeDesignation' => $yearDesignation,
                'search' => 'Mika',
            ]))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Student - Mika Yearmatch'])
            ->assertJsonMissing(['label' => 'Student - Rico Noteligible']);

        $this->actingAs($this->admin)
            ->getJson(route('admin.office-designations.eligible-users', $staffDesignation))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Staff - Library Staff Holder'])
            ->assertJsonMissing(['label' => 'Student - Paolo Programmatch']);
    }

    public function test_admin_cannot_create_office_account_with_duplicate_username(): void
    {
        $existing = User::factory()->office()->create(['username' => 'duplicate.office']);

        $this->actingAs($this->admin)
            ->post(route('admin.office-accounts.store'), [
                'display_name' => 'Duplicate Office',
                'office_type' => OfficeAccount::TYPE_LIBRARIAN,
                'username' => $existing->username,
                'password' => 'password',
            ])
            ->assertRedirect(route('admin.office-accounts.index'))
            ->assertSessionHasErrors(['username'], null, 'officeAccountCreate');
    }

    public function test_admin_can_delete_program_only_when_no_students_are_assigned(): void
    {
        $program = Program::factory()->create(['code' => 'BSSW']);

        $this->actingAs($this->admin)
            ->delete(route('admin.programs.destroy', $program), [
                'delete_confirmation' => 'DELETE BSSW',
            ])
            ->assertRedirect(route('admin.programs.index'))
            ->assertSessionHas('success', 'Program deleted successfully.');

        $this->assertDatabaseMissing('programs', ['code' => 'BSSW']);
    }

    public function test_program_delete_is_not_allowed_while_students_are_assigned(): void
    {
        $program = Program::factory()->create(['code' => 'BSEC']);
        Student::factory()->create(['program_id' => $program->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.programs.destroy', $program), [
                'delete_confirmation' => 'DELETE BSEC',
            ])
            ->assertRedirect(route('admin.programs.index'))
            ->assertSessionHas('error', 'Program cannot be deleted while students are assigned to it.');

        $this->assertDatabaseHas('programs', ['code' => 'BSEC']);
    }

    public function test_registration_request_search_rejects_overlong_input_before_querying(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.registration-requests.index'))
            ->get(route('admin.registration-requests.index', [
                'search' => str_repeat('A', 101),
            ]))
            ->assertRedirect(route('admin.registration-requests.index'))
            ->assertSessionHasErrors('search');
    }

    public function test_program_delete_is_not_allowed_while_registration_requests_exist(): void
    {
        $program = Program::factory()->create(['code' => 'BSCR']);
        StudentRegistrationRequest::factory()->create(['program_id' => $program->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.programs.destroy', $program), [
                'delete_confirmation' => 'DELETE BSCR',
            ])
            ->assertRedirect(route('admin.programs.index'))
            ->assertSessionHas('error', 'Program cannot be deleted while mobile registration requests are using it.');

        $this->assertDatabaseHas('programs', ['code' => 'BSCR']);
    }
}
