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

    public function test_mobile_registration_options_return_programs_year_levels_and_suffixes(): void
    {
        $this->getJson('/api/registration/options')
            ->assertOk()
            ->assertJsonPath('programs.0.code', 'AS')
            ->assertJsonFragment(['label' => '1st Year'])
            ->assertJsonPath('name_extensions.0', 'Jr');
    }

    public function test_student_can_register_from_mobile_app(): void
    {
        $program = \App\Models\Program::where('code', 'BSIT')->firstOrFail();

        $this->postJson('/api/register', [
            'student_id_number' => '2407777',
            'first_name' => 'niña',
            'middle_initial' => 'ñ',
            'last_name' => 'dela cruz',
            'name_extension' => 'Jr',
            'email' => 'nina.delacruz@lnu.edu.ph',
            'program_id' => $program->id,
            'year_level' => 2,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Account created successfully. Please sign in.')
            ->assertJsonPath('student.student_id_number', '2407777')
            ->assertJsonPath('student.program.code', 'BSIT');

        $this->assertDatabaseHas('students', [
            'student_id_number' => '2407777',
            'program_id' => $program->id,
            'year_level' => 2,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => '2407777',
            'first_name' => 'niña',
            'middle_initial' => 'Ñ',
            'last_name' => 'dela cruz',
            'name_extension' => 'Jr',
            'email' => 'nina.delacruz@lnu.edu.ph',
            'role' => \App\Models\User::ROLE_STUDENT,
        ]);
    }

    public function test_mobile_registration_rejects_admin_student_rule_violations(): void
    {
        $program = \App\Models\Program::where('code', 'BSIT')->firstOrFail();
        $futureStudentId = now()->addYear()->format('y') . '00001';

        $this->postJson('/api/register', [
            'student_id_number' => $futureStudentId,
            'first_name' => 'Bad😊',
            'middle_initial' => '12',
            'last_name' => 'Student',
            'email' => 'bad@example.com',
            'program_id' => $program->id,
            'year_level' => 5,
            'password' => 'password',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'student_id_number',
                'first_name',
                'middle_initial',
                'email',
                'year_level',
                'password',
            ]);
    }

    public function test_student_login_validation_errors_return_message_and_errors_payload(): void
    {
        $this->postJson('/api/login', [
            'student_id' => '',
            'password' => '',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The student id field is required. (and 1 more error)')
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'student_id',
                    'password',
                ],
            ]);
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
