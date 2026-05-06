<?php

namespace Tests\Unit;

use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAndOfficeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_gets_admin_portal_route_and_label(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($user->canAccessAdminPortal());
        $this->assertSame('admin.dashboard', $user->portalDashboardRoute());
        $this->assertSame('Super Admin', $user->portalRoleLabel());
    }

    public function test_office_user_can_access_office_portal_without_extra_checks(): void
    {
        $user = User::factory()->office()->create();

        $this->assertTrue($user->canAccessOfficePortal());
        $this->assertSame('office.dashboard', $user->portalDashboardRoute());
    }

    public function test_student_without_designation_has_no_web_portal_route(): void
    {
        $user = User::factory()->student()->create();

        $this->assertFalse($user->canAccessOfficePortal());
        $this->assertNull($user->portalDashboardRoute());
    }

    public function test_student_with_active_office_designation_can_access_office_portal(): void
    {
        $student = Student::factory()->create();
        $designation = OfficeDesignation::factory()->yearLevelTreasurer($student->year_level)->create();

        OfficeDesignationAssignment::factory()->create([
            'office_designation_id' => $designation->id,
            'user_id' => $student->user_id,
        ]);

        $this->assertTrue($student->user->canAccessOfficePortal());
        $this->assertSame('Student Office Holder', $student->user->portalRoleLabel());
    }

    public function test_inactive_designation_assignment_does_not_grant_office_access(): void
    {
        $student = Student::factory()->create();
        $designation = OfficeDesignation::factory()->yearLevelTreasurer($student->year_level)->create();

        OfficeDesignationAssignment::factory()->inactive()->create([
            'office_designation_id' => $designation->id,
            'user_id' => $student->user_id,
        ]);

        $this->assertFalse($student->user->canAccessOfficePortal());
    }

    public function test_student_formatted_name_uses_structured_parts_before_fallback(): void
    {
        $user = User::factory()
            ->namedStudent('Maria', 'c.', 'Del Rosario', 'jr')
            ->create(['name' => 'Fallback Name']);

        $this->assertSame('Maria C. Del Rosario Jr', $user->formattedName());
        $this->assertSame([
            'first_name' => 'Maria',
            'middle_initial' => 'C',
            'last_name' => 'Del Rosario',
            'name_extension' => 'Jr',
        ], $user->studentNameParts());
    }

    public function test_non_student_formatted_name_uses_account_name(): void
    {
        $user = User::factory()->office()->create(['name' => 'Library Office']);

        $this->assertSame('Library Office', $user->formattedName());
    }

    public function test_student_display_name_delegates_to_user_formatted_name(): void
    {
        $student = Student::factory()
            ->for(User::factory()->namedStudent('Juan', 'D', 'Santos'), 'user')
            ->create();

        $this->assertSame('Juan D. Santos', $student->displayName());
    }

    public function test_student_year_level_labels_cover_standard_and_unknown_years(): void
    {
        $firstYear = Student::factory()->make(['year_level' => 1]);
        $unknownYear = Student::factory()->make(['year_level' => 6]);

        $this->assertSame('1st Year', $firstYear->yearLevelLabel());
        $this->assertSame('6th Year', $unknownYear->yearLevelLabel());
    }

    public function test_office_account_form_type_options_only_show_staff_types_for_new_accounts(): void
    {
        $options = OfficeAccount::formTypeOptions();

        $this->assertArrayHasKey(OfficeAccount::TYPE_ACAD_ORG_ADVISER, $options);
        $this->assertArrayHasKey(OfficeAccount::TYPE_LIBRARIAN, $options);
        $this->assertArrayHasKey(OfficeAccount::TYPE_VPSD, $options);
        $this->assertArrayNotHasKey(OfficeAccount::TYPE_ACAD_ORG_TREASURER, $options);
        $this->assertArrayNotHasKey(OfficeAccount::TYPE_YEAR_LEVEL_TREASURER, $options);
    }

    public function test_office_account_form_type_options_keep_legacy_student_led_type_when_editing(): void
    {
        $officeAccount = OfficeAccount::factory()->yearLevelTreasurer(3)->create();

        $this->assertArrayHasKey(
            OfficeAccount::TYPE_YEAR_LEVEL_TREASURER,
            OfficeAccount::formTypeOptions($officeAccount)
        );
    }

    public function test_office_account_scope_requirements_are_based_on_office_type(): void
    {
        $this->assertTrue(OfficeAccount::requiresProgramScopeForType(OfficeAccount::TYPE_ACAD_ORG_ADVISER));
        $this->assertTrue(OfficeAccount::requiresYearLevelScopeForType(OfficeAccount::TYPE_YEAR_LEVEL_TREASURER));
        $this->assertFalse(OfficeAccount::requiresProgramScopeForType(OfficeAccount::TYPE_LIBRARIAN));
        $this->assertFalse(OfficeAccount::requiresYearLevelScopeForType(OfficeAccount::TYPE_VPSD));
    }

    public function test_office_account_scope_label_and_summary_reflect_program_scope(): void
    {
        $program = Program::factory()->create(['code' => 'BSIT']);
        $officeAccount = OfficeAccount::factory()->academicOrgAdviser($program)->create();

        $this->assertSame('BSIT', $officeAccount->scopeLabel());
        $this->assertSame('Program: BSIT', $officeAccount->scopeSummaryLabel());
    }

    public function test_office_account_scope_label_and_summary_reflect_year_level_scope(): void
    {
        $officeAccount = OfficeAccount::factory()->yearLevelTreasurer(4)->create();

        $this->assertSame('4th Year', $officeAccount->scopeLabel());
        $this->assertSame('Year Level: 4th Year', $officeAccount->scopeSummaryLabel());
    }

    public function test_global_office_account_summary_is_global(): void
    {
        $officeAccount = OfficeAccount::factory()->vpsd()->create();

        $this->assertNull($officeAccount->scopeLabel());
        $this->assertSame('Global: all programs and year levels', $officeAccount->scopeSummaryLabel());
    }

    public function test_designation_display_names_use_program_and_year_context(): void
    {
        $program = Program::factory()->create(['code' => 'BSIT', 'org_name' => 'DIGITS']);
        $adviser = OfficeAccount::factory()->academicOrgAdviser($program)->create();
        $yearTreasurer = OfficeAccount::factory()->yearLevelTreasurer(2)->create();

        $this->assertSame('DIGITS Academic Organization Adviser', $adviser->designationDisplayName());
        $this->assertSame('2nd Year Level Organization Treasurer', $yearTreasurer->designationDisplayName());
    }

    public function test_non_student_designation_accepts_any_staff_office_account(): void
    {
        $designation = OfficeDesignation::factory()->vpsd()->create();
        $officeAccount = OfficeAccount::factory()->academicOrgAdviser()->create();

        $this->assertTrue($designation->matchesUser($officeAccount->user));
    }

    public function test_student_led_designation_rejects_staff_office_account(): void
    {
        $designation = OfficeDesignation::factory()->yearLevelTreasurer(3)->create();
        $officeAccount = OfficeAccount::factory()->vpsd()->create();

        $this->assertFalse($designation->matchesUser($officeAccount->user));
    }

    public function test_program_treasurer_designation_accepts_student_from_same_program_only(): void
    {
        $program = Program::factory()->create();
        $otherProgram = Program::factory()->create();
        $designation = OfficeDesignation::factory()->academicOrgTreasurer($program)->create();
        $matchingStudent = Student::factory()->create(['program_id' => $program->id]);
        $otherStudent = Student::factory()->create(['program_id' => $otherProgram->id]);

        $this->assertTrue($designation->matchesUser($matchingStudent->user));
        $this->assertFalse($designation->matchesUser($otherStudent->user));
    }

    public function test_year_level_designation_accepts_student_from_same_year_only(): void
    {
        $designation = OfficeDesignation::factory()->yearLevelTreasurer(3)->create();
        $matchingStudent = Student::factory()->create(['year_level' => 3]);
        $otherStudent = Student::factory()->create(['year_level' => 2]);

        $this->assertTrue($designation->matchesUser($matchingStudent->user));
        $this->assertFalse($designation->matchesUser($otherStudent->user));
    }

    public function test_non_student_designation_rejects_student_record(): void
    {
        $designation = OfficeDesignation::factory()->librarian()->create();
        $student = Student::factory()->create();

        $this->assertFalse($designation->matchesUser($student->user));
    }

    public function test_active_office_designations_relationship_only_returns_active_assignments(): void
    {
        $user = User::factory()->office()->create();
        $activeDesignation = OfficeDesignation::factory()->vpsd()->create();
        $inactiveDesignation = OfficeDesignation::factory()->librarian()->create();

        OfficeDesignationAssignment::factory()->create([
            'user_id' => $user->id,
            'office_designation_id' => $activeDesignation->id,
        ]);
        OfficeDesignationAssignment::factory()->inactive()->create([
            'user_id' => $user->id,
            'office_designation_id' => $inactiveDesignation->id,
        ]);

        $this->assertSame(
            [$activeDesignation->id],
            $user->activeOfficeDesignations()->pluck('office_designations.id')->all()
        );
    }

    public function test_user_factory_password_is_usable_for_authentication_checks(): void
    {
        $user = User::factory()->student()->create();

        $this->assertTrue(Hash::check('password', $user->password));
    }
}
