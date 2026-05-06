<?php

namespace Tests\Feature;

use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PortalAndStudentApiEdgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_login_page_uses_digital_clearance_portal_copy(): void
    {
        $this->get(route('portal.login'))
            ->assertOk()
            ->assertSee('Digital Clearance Login Portal')
            ->assertDontSee('Shared Portal');
    }

    public function test_invalid_web_login_keeps_username_and_does_not_authenticate(): void
    {
        User::factory()->admin()->create([
            'username' => 'mis.admin',
        ]);

        $this->from(route('portal.login'))
            ->post(route('portal.login.submit'), [
                'username' => 'mis.admin',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('portal.login'))
            ->assertSessionHasInput('username', 'mis.admin')
            ->assertSessionHasErrors(['username'], null, 'portalLogin');

        $this->assertGuest();
    }

    public function test_student_without_designation_cannot_use_web_portal_login(): void
    {
        $student = Student::factory()->create();

        $this->post(route('portal.login.submit'), [
            'username' => $student->student_id_number,
            'password' => 'password',
        ])
            ->assertRedirect(route('portal.login'))
            ->assertSessionHasErrors(['username'], null, 'portalLogin');

        $this->assertGuest();
    }

    public function test_student_designation_holder_can_use_web_portal_login(): void
    {
        $student = Student::factory()->create(['year_level' => 3]);
        $student->user->update(['username' => $student->student_id_number]);
        $designation = OfficeDesignation::factory()->yearLevelTreasurer(3)->create();
        OfficeDesignationAssignment::factory()->create([
            'office_designation_id' => $designation->id,
            'user_id' => $student->user_id,
        ]);

        $this->post(route('portal.login.submit'), [
            'username' => $student->student_id_number,
            'password' => 'password',
        ])
            ->assertRedirect(route('office.dashboard'));

        $this->assertAuthenticatedAs($student->user);
    }

    public function test_logout_clears_web_session_and_redirects_to_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('portal.logout'))
            ->assertRedirect(route('portal.login'))
            ->assertSessionHas('info', 'You have been signed out.');

        $this->assertGuest();
    }

    public function test_mobile_login_requires_student_id_and_password_fields(): void
    {
        $this->postJson(route('api.login'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['student_id', 'password']);
    }

    public function test_mobile_login_rejects_office_account_even_with_matching_username(): void
    {
        $office = User::factory()->office()->create(['username' => 'office.user']);

        $this->postJson(route('api.login'), [
            'student_id' => $office->username,
            'password' => 'password',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid student ID or password.');
    }

    public function test_mobile_logout_deletes_current_token_only(): void
    {
        $student = Student::factory()->create();
        $currentToken = $student->user->createToken('current')->plainTextToken;
        $student->user->createToken('other-device');

        $this->withHeader('Authorization', 'Bearer ' . $currentToken)
            ->postJson(route('api.logout'))
            ->assertOk()
            ->assertJsonPath('message', 'Successfully logged out.');

        $this->assertSame(1, $student->user->tokens()->count());
    }

    public function test_me_endpoint_returns_404_when_student_profile_is_missing(): void
    {
        $user = User::factory()->student()->create();
        Sanctum::actingAs($user);

        $this->getJson(route('api.me'))
            ->assertStatus(404)
            ->assertJsonPath('message', 'Student profile not found.');
    }

    public function test_current_clearance_payload_handles_no_active_semester(): void
    {
        $student = Student::factory()->create();
        Semester::factory()->create(['is_active' => false]);
        Sanctum::actingAs($student->user);

        $this->getJson(route('api.clearance.current'))
            ->assertOk()
            ->assertJsonPath('active_semester', null)
            ->assertJsonPath('clearance', null);
    }
}
