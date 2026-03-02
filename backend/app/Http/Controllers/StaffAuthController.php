<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffAuthController extends Controller
{
    /**
     * Show the staff login form
     */
    public function showLogin()
    {
        return view('staff.login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request)
    {
        // Validate inputs with custom messages
        $credentials = $request->validate(
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'min:6'],
            ],
            [
                'email.required' => 'Email is required.',
                'email.email' => 'Please enter a valid email address.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 6 characters.',
            ]
        );

        // Attempt login
        if (Auth::guard('web')->attempt($credentials)) {

            // Regenerate session for security
            $request->session()->regenerate();

            // Check if user is staff
            if (!Auth::user()->is_staff) {
                Auth::logout();

                // Prevent 419 error
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'You are not authorized as staff.'
                ]);
            }

            // ✅ Redirect to dashboard with success message
            return redirect()->route('dashboard')
                ->with('success', 'Login successful. Welcome!');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.'
        ]);
    }
}