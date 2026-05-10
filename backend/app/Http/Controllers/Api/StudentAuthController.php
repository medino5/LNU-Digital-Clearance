<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\ProfilePhotoStorage;
use App\Support\StudentClearancePayloadBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }
}
