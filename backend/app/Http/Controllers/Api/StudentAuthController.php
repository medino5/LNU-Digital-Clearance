<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Models\Semester;
use App\Models\Student;
use App\Support\ProfilePhotoStorage;
use App\Support\StudentClearancePayloadBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        if ($activeClearance && $activeClearance->status !== Clearance::STATUS_COMPLETED) {
            throw ValidationException::withMessages([
                'profile' => ['Academic profile can only be edited before starting clearance or after completing the current clearance.'],
            ]);
        }

        $data = $request->validate([
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after_or_equal:1900-01-01'],
        ], [
            'program_id.required' => 'Choose a program.',
            'year_level.required' => 'Choose a year level.',
            'year_level.between' => 'Year level must be from 1st to 4th year.',
            'date_of_birth.required' => 'Birthday is required.',
            'date_of_birth.date_format' => 'Birthday must use the YYYY-MM-DD format.',
            'date_of_birth.before_or_equal' => 'Birthday cannot be in the future.',
            'date_of_birth.after_or_equal' => 'Birthday is outside the supported range.',
        ]);

        $student->update([
            'program_id' => $data['program_id'],
            'year_level' => $data['year_level'],
            'date_of_birth' => $data['date_of_birth'],
        ]);

        $student->refresh()->load('user', 'program');

        return response()->json([
            'message' => 'Academic profile updated successfully.',
            'profile' => $this->payloadBuilder->build($student, null, null)['student'],
        ]);
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
