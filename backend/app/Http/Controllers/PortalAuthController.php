<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PortalAuthController extends Controller
{
    public function landing(Request $request)
    {
        if (! $request->user()) {
            return redirect()->route('portal.login');
        }

        $dashboardRoute = $this->dashboardRouteForRole($request->user()->role);

        if (! $dashboardRoute) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login');
        }

        return redirect()->route($dashboardRoute);
    }

    public function showLogin()
    {
        return view('auth.login', [
            'portalTitle' => 'Shared Portal Login',
            'portalSubtitle' => 'Super admin and office account access',
            'submitRoute' => route('portal.login.submit'),
            'usernameLabel' => 'Username',
        ]);
    }

    public function login(Request $request)
    {
        return $this->attemptLogin($request);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }

    protected function attemptLogin(Request $request)
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

        $dashboardRoute = $this->dashboardRouteForRole($request->user()->role);

        if (! $dashboardRoute) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'username' => 'This account is not allowed to sign in to the web portal.',
            ])->onlyInput('username');
        }

        return redirect()->route($dashboardRoute);
    }

    protected function dashboardRouteForRole(?string $role): ?string
    {
        return match ($role) {
            'admin' => 'admin.dashboard',
            'office' => 'office.dashboard',
            default => null,
        };
    }
}
