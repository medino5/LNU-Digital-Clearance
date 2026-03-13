<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // ===============================
    // LOGIN (Used by Flutter App)
    // ===============================
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid email or password.'
            ], 401);
        }

        // Generate the token for the mobile app
        $token = $user->createToken('mobile_app_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_student' => $user->is_student,
                'is_staff' => $user->is_staff,
            ]
        ]);
    }

    // ===============================
    // LOGOUT (Destroys the Token)
    // ===============================
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    // ===============================
    // GET CURRENT USER (Verifies Token)
    // ===============================
    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('program')
        ]);
    }
}