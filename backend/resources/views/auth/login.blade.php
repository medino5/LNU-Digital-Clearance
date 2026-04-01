@extends('layouts.portal', ['title' => $portalTitle])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))

    <div class="topbar">
        <div>
            <h1>{{ $portalTitle }}</h1>
            <p>{{ $portalSubtitle }}</p>
        </div>
    </div>

    <div class="content" style="max-width: 520px; margin: 0 auto;">
        @if(auth()->check())
            @php($activeUser = auth()->user())
            @php($currentDashboardRoute = $activeUser->portalDashboardRoute())
            <div class="callout success" style="margin-bottom: 20px;">
                <strong>Current session:</strong>
                {{ $activeUser->formattedName() ?: ($activeUser->name ?? $activeUser->username) }}
                ({{ $activeUser->portalRoleLabel() }}).
                Signing in here will replace the current portal session.
                <div class="actions-inline" style="margin-top: 12px;">
                    @if($currentDashboardRoute)
                        <a class="button secondary" href="{{ route($currentDashboardRoute) }}">
                            Return to Current Dashboard
                        </a>
                    @endif
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

        @if($validationErrors->isNotEmpty())
            <div class="callout error" style="margin-bottom: 20px;">
                {{ $validationErrors->first() }}
            </div>
        @endif

        <div class="card">
            <div class="eyebrow">Secure Access</div>
            <h2 style="margin-bottom: 8px;">Sign in to continue</h2>
            <p class="muted" style="margin-top: 0;">
                Use the credentials issued by MIS. Students normally sign in through the mobile app unless they currently hold an active office designation.
            </p>
            <p class="muted" style="margin-top: 0;">
                Sign in once and the system will send you to the correct dashboard based on your current portal access.
                If another account is active, this sign-in will replace it.
            </p>

            <form method="POST" action="{{ $submitRoute }}">
                @csrf
                <label>
                    {{ $usernameLabel }}
                    <input type="text" name="username" value="{{ old('username') }}" required autofocus>
                    <x-field-error field="username" bag="portalLogin" />
                </label>

                <label>
                    Password
                    <input type="password" name="password" required>
                    <x-field-error field="password" bag="portalLogin" />
                </label>

                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>
@endsection
