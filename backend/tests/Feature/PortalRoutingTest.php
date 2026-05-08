<?php

namespace Tests\Feature;

use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\OfficeDesignationBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_root_redirects_to_shared_portal_login(): void
    {
        // The shared portal login is the guest entry point for the web app.
        $this->get('/')
            ->assertRedirect(route('portal.login'));
    }

    public function test_authenticated_admin_root_redirects_to_admin_dashboard(): void
    {
        // Root now acts as a role-based landing route for signed-in users.
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_authenticated_office_root_redirects_to_office_dashboard(): void
    {
        $officeUser = User::factory()->create([
            'name' => 'Office User',
            'username' => 'office.user',
            'password' => Hash::make('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $officeAccount = OfficeAccount::create([
            'user_id' => $officeUser->id,
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        app(OfficeDesignationBackfill::class)->syncOfficeAccount($officeAccount);

        $this->actingAs($officeUser)
            ->get('/')
            ->assertRedirect(route('office.dashboard'));
    }

    public function test_authenticated_admin_can_view_shared_login_page(): void
    {
        // Signed-in users can still open the shared login page to switch
        // accounts without getting trapped on the dashboard.
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('portal.login'))
            ->assertOk()
            ->assertSee('Digital Clearance Login Portal');
    }

    public function test_shared_login_routes_admin_to_admin_dashboard(): void
    {
        // The shared login should route admins to the admin dashboard after a
        // successful sign-in.
        $admin = User::factory()->create([
            'name' => 'MIS Admin',
            'username' => 'mis.admin.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $response = $this->post(route('portal.login.submit'), [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_authenticated_admin_can_switch_to_office_account_from_shared_login(): void
    {
        // Signing into another role from the shared login replaces the active
        // session and lands on the correct dashboard.
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $officeUser = User::factory()->create([
            'name' => 'Office User',
            'username' => 'office.user',
            'password' => Hash::make('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $officeAccount = OfficeAccount::create([
            'user_id' => $officeUser->id,
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        app(OfficeDesignationBackfill::class)->syncOfficeAccount($officeAccount);

        $response = $this->actingAs($admin)->post(route('portal.login.submit'), [
            'username' => 'office.user',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('office.dashboard'));

        $this->assertAuthenticatedAs($officeUser);

        $this->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Sign Out');
    }

    public function test_shared_login_rejects_student_accounts(): void
    {
        // Students without portal access are intentionally kept on the mobile
        // app and should not be able to enter the shared web portal.
        $student = User::factory()->create([
            'username' => 'student.user',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'is_student' => true,
            'is_staff' => false,
        ]);

        $response = $this->from(route('portal.login'))->post(route('portal.login.submit'), [
            'username' => $student->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('portal.login'));
        $response->assertSessionHasErrorsIn('portalLogin', ['username']);
        $this->assertGuest();
    }

    public function test_shared_login_routes_student_designation_holder_to_office_dashboard(): void
    {
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'org_name' => 'DIGITS',
        ]);

        $studentUser = User::factory()->create([
            'name' => 'Working Student',
            'username' => 'working.student',
            'password' => Hash::make('password'),
            'role' => User::ROLE_STUDENT,
            'is_student' => true,
            'is_staff' => false,
        ]);

        Student::create([
            'user_id' => $studentUser->id,
            'student_id_number' => '2401234',
            'program_id' => $program->id,
            'year_level' => 3,
        ]);

        $designation = OfficeDesignation::create([
            'key' => 'year-3-treasurer',
            'display_name' => '3rd Year Level Organization Treasurer',
            'office_type' => OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER,
            'program_id' => null,
            'year_level' => 3,
            'is_active' => true,
        ]);

        OfficeDesignationAssignment::create([
            'office_designation_id' => $designation->id,
            'user_id' => $studentUser->id,
            'assigned_by_user_id' => null,
            'assigned_at' => now(),
            'released_at' => null,
            'is_active' => true,
        ]);

        $response = $this->post(route('portal.login.submit'), [
            'username' => $studentUser->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('office.dashboard'));
        $this->assertAuthenticatedAs($studentUser);
    }

    public function test_shared_login_renders_inline_validation_feedback_for_missing_fields(): void
    {
        $this->from(route('portal.login'))
            ->followingRedirects()
            ->post(route('portal.login.submit'), [
                'username' => '',
                'password' => '',
            ])
            ->assertOk()
            ->assertSee('The username field is required.')
            ->assertSee('The password field is required.');
    }

    public function test_legacy_portal_login_urls_redirect_to_shared_login(): void
    {
        // Old login entry points should stay usable while the shared portal
        // route becomes the new canonical destination.
        $this->get(route('admin.login'))->assertRedirect(route('portal.login'));
        $this->get(route('office.login'))->assertRedirect(route('portal.login'));
    }

    public function test_admin_dashboard_shows_logout_and_switch_actions(): void
    {
        // The admin shell should expose route-based navigation and a logout
        // action after the admin redesign work.
        $admin = User::factory()->create([
            'name' => 'MIS Admin',
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Programs')
            ->assertSee('Log Out');
    }

    public function test_office_dashboard_shows_logout_and_switch_actions(): void
    {
        // Office users should see the same shared-login and logout actions.
        $officeUser = User::factory()->create([
            'name' => 'Office User',
            'username' => 'office.user',
            'password' => Hash::make('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $officeAccount = OfficeAccount::create([
            'user_id' => $officeUser->id,
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        app(OfficeDesignationBackfill::class)->syncOfficeAccount($officeAccount);

        $this->actingAs($officeUser)
            ->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Sign Out');
    }
}
