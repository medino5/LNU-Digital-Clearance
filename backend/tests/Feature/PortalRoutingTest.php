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
        $this->get('/')
            ->assertRedirect(route('office.login'));
    }

    public function test_authenticated_admin_can_view_office_login_page(): void
    {
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
