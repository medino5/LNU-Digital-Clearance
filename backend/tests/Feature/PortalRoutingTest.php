<?php

namespace Tests\Feature;

use App\Models\OfficeAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PortalRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_office_login(): void
    {
        // This documents the default browser entry point for the rehauled
        // portal setup: the root URL should lead to the office login page.
        $this->get('/')
            ->assertRedirect(route('office.login'));
    }

    public function test_authenticated_admin_can_view_office_login_page(): void
    {
        // This covers the portal-switching fix: an authenticated admin should
        // still be able to open the office login page instead of being trapped
        // on the admin dashboard.
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('office.login'))
            ->assertOk()
            ->assertSee('Office Login')
            ->assertSee('Signing in here will replace the current portal session.')
            ->assertSee('Log Out / Switch Account');
    }

    public function test_authenticated_admin_can_switch_to_office_portal(): void
    {
        // This proves that signing into another portal replaces the current
        // browser session and lands on the correct office dashboard.
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

        OfficeAccount::create([
            'user_id' => $officeUser->id,
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        $response = $this->actingAs($admin)->post(route('office.login.submit'), [
            'username' => 'office.user',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('office.dashboard'));

        $this->assertAuthenticatedAs($officeUser);

        $this->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Log Out / Switch Account');
    }

    public function test_admin_dashboard_shows_logout_and_switch_actions(): void
    {
        // This keeps the super admin dashboard usable by asserting the visible
        // session controls that were added during the portal rehaul.
        $admin = User::factory()->create([
            'name' => 'MIS Admin',
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Switch to Office Portal')
            ->assertSee('Log Out / Switch Account');
    }

    public function test_office_dashboard_shows_logout_and_switch_actions(): void
    {
        // This mirrors the admin check for office users so logout and portal
        // switching stay discoverable after future UI changes.
        $officeUser = User::factory()->create([
            'name' => 'Office User',
            'username' => 'office.user',
            'password' => Hash::make('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        OfficeAccount::create([
            'user_id' => $officeUser->id,
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);

        $this->actingAs($officeUser)
            ->get(route('office.dashboard'))
            ->assertOk()
            ->assertSee('Switch to Admin Portal')
            ->assertSee('Log Out / Switch Account');
    }
}
