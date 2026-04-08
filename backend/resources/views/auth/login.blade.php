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
        @if($currentUser)
            <div class="callout success" style="margin-bottom: 20px;">
                <strong>Current session:</strong>
                {{ $currentUser->formattedName() ?: ($currentUser->name ?? $currentUser->username) }}
                ({{ $currentUser->portalRoleLabel() }}).
                Signing in again will replace the current portal session after login.

                <div class="actions-inline" style="margin-top: 12px;">
                    @if($currentDashboardRoute)
                        <a class="button secondary" href="{{ $currentDashboardRoute }}">
                            Return to Current Dashboard
                        </a>
                    @endif
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
                        <input
                            type="password"
                            name="password"
                            id="password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle
                            data-target="password"
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
.password-wrapper {
    display: grid;
    gap: 6px;
}

.password-field {
    position: relative;
}

.password-field input {
    width: 100%;
    padding-right: 52px;
}

.password-field .password-toggle {
    position: absolute;
    top: 50%;
    right: 14px;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    padding: 0;
    margin: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    color: var(--muted);
    box-shadow: none;
    cursor: pointer;
    z-index: 2;
}

.password-field .password-toggle:hover,
.password-field .password-toggle:focus-visible {
    background: transparent;
    color: var(--navy);
    outline: none;
    transform: translateY(-50%);
}

.password-field .password-toggle svg {
    width: 18px;
    height: 18px;
    display: block;
    pointer-events: none;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (toggleButton) {
        const targetId = toggleButton.dataset.target;
        const passwordInput = document.getElementById(targetId);
        const icon = toggleButton.querySelector('[data-password-toggle-icon]');

        if (!passwordInput) {
            return;
        }

        toggleButton.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
            toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            toggleButton.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
        });
    });

    document.querySelectorAll('form[data-loading-form]').forEach(function (form) {
        let isSubmitting = false;

        form.addEventListener('submit', function (event) {
            if (isSubmitting) {
                event.preventDefault();
                return;
            }

            const submitButton = form.querySelector('[data-loading-button]');
            if (!submitButton) {
                isSubmitting = true;
                return;
            }

            isSubmitting = true;
            submitButton.disabled = true;
            submitButton.textContent =
                submitButton.dataset.loadingText || 'Processing...';
        });
    });
});
</script>
@endpush