<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalAuthController extends Controller
{
    public function showAdminLogin()
    {
        return view('auth.login', [
            'portalTitle' => 'Super Admin Login',
            'portalSubtitle' => 'MIS account access',
            'submitRoute' => route('admin.login.submit'),
            'usernameLabel' => 'Username',
        ]);
    }

    public function showOfficeLogin()
    {
        return view('auth.login', [
            'portalTitle' => 'Office Login',
            'portalSubtitle' => 'Position-based office account access',
            'submitRoute' => route('office.login.submit'),
            'usernameLabel' => 'Username',
        ]);
    }

    public function loginAdmin(Request $request)
    {
        return $this->attemptLogin($request, 'admin', 'admin.dashboard');
    }

    public function loginOffice(Request $request)
    {
        return $this->attemptLogin($request, 'office', 'office.dashboard');
    }

    public function logout(Request $request)
    {
        $redirectRoute = $request->user()?->role === 'admin'
            ? 'admin.login'
            : 'office.login';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($redirectRoute);
    }

    protected function attemptLogin(Request $request, string $role, string $routeName)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if ($request->user()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (!Auth::attempt($credentials)) {
            return back()->withErrors([
                'username' => 'Invalid credentials.',
            ])->onlyInput('username');
        }

        $request->session()->regenerate();

        if ($request->user()->role !== $role) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'username' => 'This account is not allowed to sign in here.',
            ])->onlyInput('username');
        }

        return redirect()->route($routeName);
    }
}
