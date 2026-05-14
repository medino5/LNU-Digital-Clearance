<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Support\ProfilePhotoStorage;
use App\Support\StudentClearancePayloadBuilder;
use App\Support\StudentNameFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentAuthController extends Controller
{
    public function __construct(
        protected StudentClearancePayloadBuilder $payloadBuilder,
    ) {
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'student_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $student = Student::with(['user', 'program'])
            ->where('student_id_number', $credentials['student_id'])
            ->first();

        if (
            !$student ||
            !$student->user ||
            !$student->user->isStudent() ||
            !Hash::check($credentials['password'], $student->user->password)
        ) {
            return response()->json([
                'message' => 'Invalid student ID or password.',
            ], 401);
        }

        $student->user->tokens()->delete();
        $token = $student->user->createToken('student_mobile_app')->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'profile' => $this->payloadBuilder->build($student, null, null)['student'],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }

    public function me(Request $request)
    {
        $student = $request->user()->loadMissing('studentProfile.program')->studentProfile;

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        return response()->json([
            'profile' => $this->payloadBuilder->build($student, null, null)['student'],
        ]);
    }

    public function updateProfilePhoto(Request $request, ProfilePhotoStorage $profilePhotoStorage)
    {
        $data = $request->validate([
            'profile_photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'profile_photo.max' => 'Profile picture must be 2 MB or smaller.',
            'profile_photo.image' => 'Choose a valid image file.',
            'profile_photo.mimes' => 'Profile picture must be JPG, PNG, or WEBP.',
        ]);

        $student = $request->user()->loadMissing('studentProfile.program')->studentProfile;

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        $profilePhotoStorage->storeForUser($student->user, $data['profile_photo']);

        return response()->json([
            'message' => 'Profile picture updated.',
            'profile' => $this->payloadBuilder->build($student->fresh(['user', 'program']), null, null)['student'],
        ]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 72 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = $request->user();

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Choose a new password that is different from your current password.'],
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function updateAcademicProfile(Request $request)
    {
        $student = $request->user()->loadMissing('studentProfile.program')->studentProfile;

        if (!$student) {
            return response()->json([
                'message' => 'Student profile not found.',
            ], 404);
        }

        $activeSemester = Semester::active()->first();
        $activeClearance = $activeSemester
            ? Clearance::query()
                ->where('student_id', $student->id)
                ->where('semester_id', $activeSemester->id)
                ->first()
            : null;

        $normalized = [];

        foreach (['first_name', 'middle_initial', 'last_name'] as $field) {
            if ($request->has($field)) {
                $normalized[$field] = trim((string) $request->input($field, ''));
            }
        }

        if ($request->has('name_extension')) {
            $normalized['name_extension'] = filled($request->input('name_extension'))
                ? trim((string) $request->input('name_extension'))
                : null;
        }

        $request->merge($normalized);

        $data = $request->validate([
            'first_name' => ['sometimes', ...$this->studentNameRules('First name')],
            'middle_initial' => ['nullable', 'string', 'size:1', 'regex:/^\pL$/u'],
            'last_name' => ['sometimes', ...$this->studentNameRules('Last name')],
            'name_extension' => ['nullable', Rule::in(User::studentNameExtensionOptions())],
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
            'year_level' => ['sometimes', 'required', 'integer', 'between:1,4'],
            'date_of_birth' => ['sometimes', 'required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
        ], [
            'program_id.required' => 'Choose a program.',
            'year_level.required' => 'Choose a year level.',
            'year_level.between' => 'Year level must be from 1st to 4th year.',
            'middle_initial.regex' => 'Middle initial must be one letter.',
            'date_of_birth.required' => 'Birthday is required.',
            'date_of_birth.date_format' => 'Birthday must use the YYYY-MM-DD format.',
            'date_of_birth.before_or_equal' => 'Birthday cannot be in the future.',
            'date_of_birth.after_or_equal' => 'Birthday is outside the supported range.',
        ]);

        $programId = array_key_exists('program_id', $data) ? (int) $data['program_id'] : (int) $student->program_id;
        $yearLevel = array_key_exists('year_level', $data) ? (int) $data['year_level'] : (int) $student->year_level;
        $academicRoutingChanged = $programId !== (int) $student->program_id
            || $yearLevel !== (int) $student->year_level;

        if (
            $academicRoutingChanged
            && $activeClearance
            && $activeClearance->status !== Clearance::STATUS_COMPLETED
        ) {
            throw ValidationException::withMessages([
                'program_id' => ['Program and year level can only be edited before starting clearance or after completing the current clearance.'],
            ]);
        }

        $nameParts = $student->user->studentNameParts();

        if (array_key_exists('first_name', $data)) {
            $nameParts['first_name'] = StudentNameFormatter::normalizeNamePart($data['first_name']) ?? '';
        }

        if (array_key_exists('middle_initial', $data)) {
            $nameParts['middle_initial'] = StudentNameFormatter::normalizeMiddleInitial($data['middle_initial'] ?? null);
        }

        if (array_key_exists('last_name', $data)) {
            $nameParts['last_name'] = StudentNameFormatter::normalizeNamePart($data['last_name']) ?? '';
        }

        if (array_key_exists('name_extension', $data)) {
            $nameParts['name_extension'] = StudentNameFormatter::normalizeExtension($data['name_extension'] ?? null);
        }

        $student->user->forceFill([
            'name' => StudentNameFormatter::compose(
                $nameParts['first_name'],
                $nameParts['middle_initial'],
                $nameParts['last_name'],
                $nameParts['name_extension'],
                $student->user->name,
            ),
            'first_name' => $nameParts['first_name'],
            'middle_initial' => $nameParts['middle_initial'],
            'last_name' => $nameParts['last_name'],
            'name_extension' => $nameParts['name_extension'],
        ])->save();

        $student->update([
            'program_id' => $programId,
            'year_level' => $yearLevel,
            'date_of_birth' => $data['date_of_birth'] ?? $student->date_of_birth,
        ]);

        $student->refresh()->load('user', 'program');
        $payload = $this->payloadBuilder->build(
            $student,
            $activeSemester,
            $activeClearance?->fresh(['steps.officeDesignation.activeUsers', 'steps.events']),
        );

        return response()->json([
            'message' => 'Profile updated successfully.',
            'profile' => $payload['student'],
            'payload' => $payload,
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function studentNameRules(string $label): array
    {
        return [
            'required',
            'string',
            'max:60',
            function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
                if (! preg_match("/^\pL[\pL'\\- ]*$/u", (string) $value)) {
                    $fail($label . ' may only contain letters, spaces, apostrophes, and hyphens.');
                }
            },
        ];
    }

    public function resetForgottenPassword(Request $request)
    {
        $data = $request->validate([
            'student_id_number' => ['required', 'string', 'regex:/^\d{7}$/'],
            'date_of_birth' => ['required', 'date_format:Y-m-d'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ], [
            'student_id_number.regex' => 'Student ID must be exactly 7 digits.',
            'date_of_birth.required' => 'Birthday is required.',
            'date_of_birth.date_format' => 'Birthday must use the YYYY-MM-DD format.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.max' => 'Password must not exceed 72 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $student = Student::with('user')
            ->where('student_id_number', $data['student_id_number'])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->first();

        if (!$student || !$student->user || !$student->user->isStudent()) {
            throw ValidationException::withMessages([
                'student_id_number' => ['No student account matches that student ID and birthday.'],
            ]);
        }

        if (Hash::check($data['password'], $student->user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Choose a new password that is different from your current password.'],
            ]);
        }

        $student->user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();
        $student->user->tokens()->delete();

        return response()->json([
            'message' => 'Password reset successful. You can now sign in.',
        ]);
    }
}
