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

        $dashboardRoute = $this->dashboardRouteForUser($request->user());

        if (! $dashboardRoute) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('portal.login');
        }

        return redirect()->route($dashboardRoute);
    }

    public function showLogin(Request $request)
    {
        $user = $request->user();
        $dashboardRoute = $user ? $this->dashboardRouteForUser($user) : null;

        return view('auth.login', [
            'portalTitle' => 'Digital Clearance Login Portal',
            'portalSubtitle' => 'Super admin and active office designation access',
            'submitRoute' => route('portal.login.submit'),
            'usernameLabel' => 'Username',
            'currentUser' => $user,
            'currentDashboardRoute' => $dashboardRoute ? route($dashboardRoute) : null,
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

        return redirect()
            ->route('portal.login')
            ->with('info', 'You have been signed out.');
    }

    protected function attemptLogin(Request $request)
    {
        $credentials = $this->validateForm(
            $request,
            'portalLogin',
            [
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
            ],
            route('portal.login'),
        );

        if ($request->user()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if (!Auth::attempt($credentials)) {
            return redirect()->to(route('portal.login'))
                ->withErrors([
                    'username' => 'Invalid credentials.',
                ], 'portalLogin')
                ->withInput($request->only('username'));
        }

        $request->session()->regenerate();

        $dashboardRoute = $this->dashboardRouteForUser($request->user());

        if (! $dashboardRoute) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(route('portal.login'))
                ->withErrors([
                    'username' => 'This account does not have web portal access.',
                ], 'portalLogin')
                ->withInput($request->only('username'));
        }

        return redirect()->route($dashboardRoute);
    }

    protected function dashboardRouteForUser($user): ?string
    {
        return $user?->portalDashboardRoute();
    }
}
