<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Clearance;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentRegistrationRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
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

    public function test_student_can_submit_registration_request_from_mobile_app(): void
    {
        $program = Program::where('code', 'BSIT')->firstOrFail();

        $this->postJson('/api/register', [
            'student_id_number' => '2407777',
            'first_name' => 'niña',
            'middle_initial' => 'ñ',
            'last_name' => 'dela cruz',
            'name_extension' => 'Jr',
            'program_id' => $program->id,
            'year_level' => 2,
            'date_of_birth' => '2005-05-21',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertAccepted()
            ->assertJsonPath('message', 'Registration submitted. Please wait for admin approval before signing in.')
            ->assertJsonPath('registration_request.student_id_number', '2407777')
            ->assertJsonPath('registration_request.program.code', 'BSIT');

        $this->assertDatabaseHas('student_registration_requests', [
            'student_id_number' => '2407777',
            'program_id' => $program->id,
            'year_level' => 2,
            'first_name' => 'niña',
            'middle_initial' => 'Ñ',
            'last_name' => 'dela cruz',
            'name_extension' => 'Jr',
            'email' => null,
            'status' => StudentRegistrationRequest::STATUS_PENDING,
        ]);

        $this->assertDatabaseMissing('students', [
            'student_id_number' => '2407777',
        ]);

        $this->assertDatabaseMissing('users', [
            'username' => '2407777',
        ]);

        $registrationRequest = StudentRegistrationRequest::where('student_id_number', '2407777')->firstOrFail();
        $this->assertTrue(Hash::check('password', $registrationRequest->password));
        $this->assertSame('2005-05-21', $registrationRequest->date_of_birth->toDateString());
    }

    public function test_admin_can_approve_mobile_registration_request_and_create_student_account(): void
    {
        $program = Program::where('code', 'BSIT')->firstOrFail();
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $registrationRequest = StudentRegistrationRequest::factory()->create([
            'student_id_number' => '2408888',
            'first_name' => 'Ana',
            'middle_initial' => 'M',
            'last_name' => 'Santos',
            'email' => 'ana.santos@lnu.edu.ph',
            'program_id' => $program->id,
            'year_level' => 3,
            'date_of_birth' => '2004-08-12',
            'password' => Hash::make('password'),
            'status' => StudentRegistrationRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.registration-requests.approve', $registrationRequest))
            ->assertRedirect(route('admin.registration-requests.index'));

        $this->assertDatabaseHas('student_registration_requests', [
            'id' => $registrationRequest->id,
            'status' => StudentRegistrationRequest::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('students', [
            'student_id_number' => '2408888',
            'program_id' => $program->id,
            'year_level' => 3,
        ]);

        $this->assertSame(
            '2004-08-12',
            Student::where('student_id_number', '2408888')->firstOrFail()->date_of_birth->toDateString(),
        );

        $this->assertDatabaseHas('users', [
            'username' => '2408888',
            'first_name' => 'Ana',
            'middle_initial' => 'M',
            'last_name' => 'Santos',
            'email' => 'ana.santos@lnu.edu.ph',
            'role' => User::ROLE_STUDENT,
        ]);
    }

    public function test_admin_can_reject_mobile_registration_request_without_reason(): void
    {
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $registrationRequest = StudentRegistrationRequest::factory()->create([
            'student_id_number' => '2409999',
            'status' => StudentRegistrationRequest::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.registration-requests.reject', $registrationRequest))
            ->assertRedirect(route('admin.registration-requests.index'));

        $this->assertDatabaseHas('student_registration_requests', [
            'id' => $registrationRequest->id,
            'status' => StudentRegistrationRequest::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'review_note' => null,
        ]);

        $this->assertDatabaseMissing('students', [
            'student_id_number' => '2409999',
        ]);
    }

    public function test_mobile_registration_rejects_duplicate_pending_student_id(): void
    {
        $program = Program::where('code', 'BSIT')->firstOrFail();

        StudentRegistrationRequest::factory()->create([
            'student_id_number' => '2405555',
            'program_id' => $program->id,
            'status' => StudentRegistrationRequest::STATUS_PENDING,
        ]);

        $this->postJson('/api/register', [
            'student_id_number' => '2405555',
            'first_name' => 'Juan',
            'last_name' => 'Reyes',
            'program_id' => $program->id,
            'year_level' => 1,
            'date_of_birth' => '2006-01-30',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id_number']);
    }

    public function test_mobile_registration_rejects_admin_student_rule_violations(): void
    {
        $program = Program::where('code', 'BSIT')->firstOrFail();
        $futureStudentId = now()->addYear()->format('y') . '00001';

        $this->postJson('/api/register', [
            'student_id_number' => $futureStudentId,
            'first_name' => 'Bad😊',
            'middle_initial' => '12',
            'last_name' => 'Student',
            'email' => 'bad@example.com',
            'program_id' => $program->id,
            'year_level' => 5,
            'date_of_birth' => now()->addDay()->format('Y-m-d'),
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
                'date_of_birth',
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
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);

        $this->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('profile.student_id_number', '2302314')
            ->assertJsonPath('profile.program.code', 'BSIT');
    }

    public function test_authenticated_student_can_load_clearance_history(): void
    {
        $clearance = $this->createCompletedSeededClearance();
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);

        $this->getJson('/api/clearance/history')
            ->assertOk()
            ->assertJsonPath('history.0.id', $clearance->id)
            ->assertJsonPath('history.0.status', 'completed')
            ->assertJsonPath('history.0.semester_label', $clearance->semester_label)
            ->assertJsonPath('history.0.academic_year', $clearance->semester->displayAcademicYear())
            ->assertJsonPath('history.0.program_code', 'BSIT')
            ->assertJsonPath('history.0.counts.total', 5)
            ->assertJsonPath('history.0.counts.approved', 5)
            ->assertJsonPath('history.0.steps.0.status', 'approved')
            ->assertJsonStructure([
                'history' => [
                    [
                        'steps' => [
                            [
                                'office_label',
                                'scope_label',
                                'status',
                                'signed_at',
                                'signed_by',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_student_can_upload_profile_picture_from_mobile_app(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);

        $this->postJson('/api/me/profile-photo', [
            'profile_photo' => UploadedFile::fake()->createWithContent(
                'profile.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
            ),
        ])
            ->assertOk()
            ->assertJsonPath('profile.student_id_number', '2302314')
            ->assertJsonPath('profile.profile_photo_url', fn ($value) => is_string($value) && str_contains($value, '/profile-photos/'));

        $student->user->refresh();
        $this->assertNotNull($student->user->profile_photo_path);
        $this->assertNotNull($student->user->profile_photo_content);

        $this->get($student->user->profilePhotoUrl())
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_authenticated_student_can_change_mobile_password(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        Sanctum::actingAs($student->user);

        $this->postJson('/api/me/password', [
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated successfully.');

        $student->user->refresh();

        $this->assertTrue(Hash::check('new-password', $student->user->password));
    }

    public function test_authenticated_student_can_update_academic_profile_before_starting_clearance(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $program = Program::where('code', 'BAEL')->firstOrFail();
        Sanctum::actingAs($student->user);

        $this->patchJson('/api/me/academic-profile', [
            'program_id' => $program->id,
            'year_level' => 4,
            'date_of_birth' => '2004-04-22',
        ])
            ->assertOk()
            ->assertJsonPath('profile.program.code', 'BAEL')
            ->assertJsonPath('profile.year_level', 4)
            ->assertJsonPath('profile.date_of_birth', '2004-04-22');

        $student->refresh();

        $this->assertSame($program->id, $student->program_id);
        $this->assertSame(4, $student->year_level);
        $this->assertSame('2004-04-22', $student->date_of_birth->toDateString());
    }

    public function test_authenticated_student_cannot_update_academic_profile_during_active_clearance(): void
    {
        $student = Student::with('user', 'program')->where('student_id_number', '2302314')->firstOrFail();
        $semester = Semester::active()->firstOrFail();
        $program = Program::where('code', 'BAEL')->firstOrFail();
        Clearance::factory()->forStudentAndSemester($student, $semester)->create([
            'status' => Clearance::STATUS_IN_PROGRESS,
        ]);
        Sanctum::actingAs($student->user);

        $this->patchJson('/api/me/academic-profile', [
            'program_id' => $program->id,
            'year_level' => 4,
            'date_of_birth' => '2004-04-22',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile']);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'program_id' => $student->program_id,
            'year_level' => $student->year_level,
        ]);
    }

    public function test_authenticated_student_can_update_academic_profile_after_completed_clearance(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $semester = Semester::active()->firstOrFail();
        $program = Program::where('code', 'BAEL')->firstOrFail();
        Clearance::factory()->forStudentAndSemester($student, $semester)->create([
            'status' => Clearance::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
        Sanctum::actingAs($student->user);

        $this->patchJson('/api/me/academic-profile', [
            'program_id' => $program->id,
            'year_level' => 2,
            'date_of_birth' => '2005-01-12',
        ])
            ->assertOk()
            ->assertJsonPath('profile.program.code', 'BAEL')
            ->assertJsonPath('profile.year_level', 2);
    }

    public function test_student_can_reset_forgotten_password_with_student_id_and_birthday(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $student->update(['date_of_birth' => '2005-03-14']);

        $this->postJson('/api/forgot-password', [
            'student_id_number' => '2302314',
            'date_of_birth' => '2005-03-14',
            'password' => 'fresh-password',
            'password_confirmation' => 'fresh-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Password reset successful. You can now sign in.');

        $student->user->refresh();

        $this->assertTrue(Hash::check('fresh-password', $student->user->password));
    }

    public function test_forgot_password_rejects_wrong_birthday(): void
    {
        $student = Student::with('user')->where('student_id_number', '2302314')->firstOrFail();
        $student->update(['date_of_birth' => '2005-03-14']);

        $this->postJson('/api/forgot-password', [
            'student_id_number' => '2302314',
            'date_of_birth' => '2005-03-15',
            'password' => 'fresh-password',
            'password_confirmation' => 'fresh-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id_number']);

        $student->user->refresh();

        $this->assertTrue(Hash::check('password', $student->user->password));
    }
}
