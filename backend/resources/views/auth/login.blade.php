@extends('layouts.portal', ['title' => $portalTitle])

@section('page')

<div class="topbar">
    <div class="topbar-left">
        <img src="{{ asset('images/lnu-logo.png') }}" alt="LNU Logo" class="topbar-logo">

        <div>
            <h1>{{ $portalTitle }}</h1>
            <p>{{ $portalSubtitle }}</p>
        </div>
    </div>
</div>

<div class="content login-container">

    {{-- Already logged in --}}
    @if($currentUser)
        <div class="callout success mb-20">
            <strong>You are signed in as:</strong>
            {{ $currentUser->formattedName() ?: ($currentUser->name ?? $currentUser->username) }}
            ({{ $currentUser->portalRoleLabel() }})
            <div class="mini mt-10">
                Signing in will switch your session.
            </div>

            <div class="actions-inline mt-10">
                @if($currentDashboardRoute)
                    <a class="button secondary" href="{{ $currentDashboardRoute }}">
                        Return to Current Dashboard
                    </a>
                @endif
            </div>
        </div>
    @endif

    {{-- Session message --}}
    @if(session('info'))
        <div class="callout success mb-20">
            {{ session('info') }}
        </div>
    @endif

    {{-- Validation error (clean + Laravel standard) --}}
    @if($errors->any())
        <div class="callout error mb-20">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="card">

        <div class="eyebrow">Secure Access</div>

        <h2>Sign in to continue</h2>

        <p class="muted">
            Use credentials issued by MIS. Access is automatically routed based on your role.
        </p>

        <form method="POST" action="{{ $submitRoute }}" data-loading-form>
            @csrf

            <label>
                {{ $usernameLabel }}
                <input type="text" name="username" value="{{ old('username') }}" required autofocus>
                <x-field-error field="username" bag="portalLogin" />
            </label>

            <label class="password-wrapper">
                Password

                <div class="password-field">
                    <input type="password" name="password" id="password" required>

                    <button
                        type="button"
                        class="password-toggle"
                        data-password-toggle
                        data-target="password"
                        aria-label="Show password"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                            fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 10s4-6 9-6 9 6 9 6-4 6-9 6-9-6-9-6z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </button>
                </div>

                <x-field-error field="password" bag="portalLogin" />
            </label>

            <button
                type="submit"
                data-loading-button
                data-default-text="Sign In"
                data-loading-text="Signing In..."
            >
                Sign In
            </button>
        </form>
    </div>
</div>

@endsection

@push('styles')
<style>

.login-logo {
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

.login-logo img {
    height: 100px;
    width: auto;
    object-fit: contain;
}

.login-container {
    width: 100%;
}

.mb-20 { margin-bottom: 20px; }
.mt-10 { margin-top: 10px; }

.password-wrapper {
    display: grid;
    gap: 6px;
}

.password-field {
    position: relative;
}

.password-field input {
    width: 100%;
    padding-right: 45px;
}

.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 14px;
    color: var(--muted);
}

.password-toggle:hover {
    color: var(--navy);
}

.password-toggle svg {
    display: block;
}

.password-field .password-toggle {
    position: absolute;
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    transition: color 0.15s ease;
}

.password-field .password-toggle:hover,
.password-field .password-toggle:focus-visible {
    color: var(--navy);
    outline: none;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.topbar-logo {
    height: 110px;
    width: auto;
    object-fit: contain;
    filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
}

.topbar {
    background: linear-gradient(135deg, #0e2742 0%, #16385f 65%, #1b4675 100%);
    color: white;
    padding: 18px 24px;
}

.topbar h1,
.topbar p {
    color: white;
}

.topbar p {
    opacity: 0.85;
}

.content.login-container {
    min-height: calc(100vh - 110px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.card {
    width: 100%;
    max-width: 520px;
    margin: 0 auto;
}

</style>
@endpush

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-password-toggle]');
    if (!btn) return;

    const input = document.getElementById(btn.dataset.target);
    if (!input) return;

    const show = input.type === 'password';

    input.type = show ? 'text' : 'password';
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});

document.addEventListener('submit', function (e) {
    const form = e.target.closest('form[data-loading-form]');
    if (!form) return;

    const btn = form.querySelector('[data-loading-button]');
    if (!btn) return;

    btn.disabled = true;
    btn.textContent = btn.dataset.loadingText || 'Loading...';
});
</script>
@endpush
