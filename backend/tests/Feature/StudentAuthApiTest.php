<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAuthApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_can_log_in_with_student_id_and_receive_profile_payload(): void
    {
        // This verifies the mobile app's entry point: a student signs in with
        // a student ID, receives a Sanctum token, and gets the profile shape
        // expected by the current Flutter app.
        $response = $this->postJson('/api/login', [
            'student_id' => '2302314',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('profile.student_id_number', '2302314')
            ->assertJsonPath('profile.program.code', 'BSIT');

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_student_login_rejects_invalid_credentials(): void
    {
        // This protects the login boundary so the mobile app gets a clean 401
        // instead of creating a session for the wrong student credentials.
        $response = $this->postJson('/api/login', [
            'student_id' => '2302314',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid student ID or password.');
    }

    public function test_student_login_replaces_previous_mobile_tokens(): void
    {
        // This confirms the current single-device token rule: logging in again
        // should invalidate older student tokens and keep only the newest one.
        $this->postJson('/api/login', [
            'student_id' => '2302314',
            'password' => 'password',
        ])->assertOk();

        $firstTokenId = PersonalAccessToken::query()->value('id');

        $this->postJson('/api/login', [
            'student_id' => '2302314',
            'password' => 'password',
        ])->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotSame($firstTokenId, PersonalAccessToken::query()->value('id'));
    }

    public function test_authenticated_student_can_load_their_profile_from_me_endpoint(): void
    {
        // This verifies the token-authenticated profile endpoint used by the
        // mobile app during session restore and profile refresh.
        $student = \App\Models\Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('profile.student_id_number', '2302314')
            ->assertJsonPath('profile.program.code', 'BSIT');
    }
}
