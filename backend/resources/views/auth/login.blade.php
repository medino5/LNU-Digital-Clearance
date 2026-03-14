@extends('layouts.portal', ['title' => $portalTitle])

@section('page')
    <div class="topbar">
        <div>
            <h1>{{ $portalTitle }}</h1>
            <p>{{ $portalSubtitle }}</p>
        </div>
        <div class="toolbar">
            <a class="button topbar-action" href="{{ route('admin.login') }}">Admin</a>
            <a class="button topbar-action" href="{{ route('office.login') }}">Office</a>
        </div>
    </div>

    <div class="content" style="max-width: 520px; margin: 0 auto;">
        @if(auth()->check())
            @php($activeUser = auth()->user())
            <div class="callout success" style="margin-bottom: 20px;">
                <strong>Current session:</strong>
                {{ $activeUser->name ?? $activeUser->username }}
                ({{ $activeUser->role === 'admin' ? 'Super Admin' : 'Office' }}).
                Signing in here will replace the current portal session.
                <div class="actions-inline" style="margin-top: 12px;">
                    <a class="button secondary" href="{{ $activeUser->role === 'admin' ? route('admin.dashboard') : route('office.dashboard') }}">
                        Return to Current Dashboard
                    </a>
                    <form method="POST" action="{{ route('portal.logout') }}" style="display: inline-grid;">
                        @csrf
                        <button type="submit" class="secondary">Log Out / Switch Account</button>
                    </form>
                </div>
            </div>
        @endif

        @if(session('info'))
            <div class="callout success" style="margin-bottom: 20px;">
                {{ session('info') }}
            </div>
        @endif

        @if($errors->any())
            <div class="callout error" style="margin-bottom: 20px;">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="card">
            <div class="eyebrow">Secure Access</div>
            <h2 style="margin-bottom: 8px;">Sign in to continue</h2>
            <p class="muted" style="margin-top: 0;">
                Use the credentials issued by MIS. Student accounts sign in through the mobile app.
            </p>
            <p class="muted" style="margin-top: 0;">
                Sign in to the selected portal below. If another account is active, this sign-in will replace it.
            </p>

            <form method="POST" action="{{ $submitRoute }}">
                @csrf
                <label>
                    {{ $usernameLabel }}
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus>
                </label>

                <label>
                    Password
                    <input type="password" name="password" required>
                </label>

                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>
@endsection
