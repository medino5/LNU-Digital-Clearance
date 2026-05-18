@extends('layouts.portal', [
    'title' => 'Registration Requests',
    'subtitle' => 'Approve mobile student account requests before students can sign in.',
])

@section('page')
    <div class="management-page">
        @include('admin.partials.page-feedback')

        <section class="management-header">
            <div>
                <h1>Registration Requests</h1>
                <p>Mobile account sign-ups stay pending here until an admin approves or rejects them.</p>
            </div>
        </section>

        <section class="management-card">
            <div class="management-card-header">
                <div>
                    <h2>Review Queue</h2>
                    <p class="management-card-kicker">Check student identity, program, year level, and section before creating the account.</p>
                </div>
            </div>

            <div class="registration-status-tabs" aria-label="Registration request status">
                <a
                    href="{{ route('admin.registration-requests.index', array_filter(['status' => 'pending', 'search' => $search, 'program_id' => $programId])) }}"
                    class="registration-status-tab pending {{ $status === 'pending' ? 'active' : '' }}"
                >
                    Pending <span>{{ $pendingCount }}</span>
                </a>
                <a
                    href="{{ route('admin.registration-requests.index', array_filter(['status' => 'approved', 'search' => $search, 'program_id' => $programId])) }}"
                    class="registration-status-tab approved {{ $status === 'approved' ? 'active' : '' }}"
                >
                    Approved <span>{{ $approvedCount }}</span>
                </a>
                <a
                    href="{{ route('admin.registration-requests.index', array_filter(['status' => 'rejected', 'search' => $search, 'program_id' => $programId])) }}"
                    class="registration-status-tab rejected {{ $status === 'rejected' ? 'active' : '' }}"
                >
                    Declined <span>{{ $rejectedCount }}</span>
                </a>
            </div>

            <form method="GET" action="{{ route('admin.registration-requests.index') }}" class="registration-search-form">
                <input type="hidden" name="status" value="{{ $status }}">

                <label>
                    Search
                    <input
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Name, ID, email, or program"
                        maxlength="50"
                    >
                </label>

                <label>
                    Program
                    <select name="program_id">
                        <option value="">All programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->id }}" @selected((string) $programId === (string) $program->id)>
                                {{ $program->code }} - {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="management-primary-action">Search</button>
                <a href="{{ route('admin.registration-requests.index') }}" class="button secondary management-secondary-action">Reset</a>
            </form>

            @if($requests->isEmpty())
                <div class="empty-state">
                    No {{ $status === 'rejected' ? 'declined' : $status }} registration requests found.
                </div>
            @else
                <div class="registration-request-list">
                    @foreach($requests as $registrationRequest)
                        <article class="registration-request-card">
                            <div class="registration-request-main">
                                <div>
                                    <div class="table-main-text">{{ $registrationRequest->displayName() }}</div>
                                    <div class="mini">
                                        {{ $registrationRequest->student_id_number }}
                                        @if($registrationRequest->email)
                                            · {{ $registrationRequest->email }}
                                        @endif
                                    </div>
                                </div>

                                <span class="badge {{ $registrationRequest->status }}">
                                    {{ ucfirst($registrationRequest->status) }}
                                </span>
                            </div>

                            <dl class="registration-request-details">
                                <div>
                                    <dt>Program</dt>
                                    <dd>{{ $registrationRequest->program?->code }} · {{ $registrationRequest->program?->name }}</dd>
                                </div>
                                <div>
                                    <dt>Year Level</dt>
                                    <dd>{{ $registrationRequest->yearLevelLabel() }}</dd>
                                </div>
                                <div>
                                    <dt>Section</dt>
                                    <dd>{{ $registrationRequest->sectionLabel() }}</dd>
                                </div>
                                <div>
                                    <dt>Birthday</dt>
                                    <dd>{{ $registrationRequest->date_of_birth?->format('M d, Y') ?? 'Not set' }}</dd>
                                </div>
                                <div>
                                    <dt>Submitted</dt>
                                    <dd>{{ $registrationRequest->created_at?->format('M d, Y g:i A') }}</dd>
                                </div>
                                @if($registrationRequest->reviewed_at)
                                    <div>
                                        <dt>Reviewed</dt>
                                        <dd>
                                            {{ $registrationRequest->reviewed_at->format('M d, Y g:i A') }}
                                            @if($registrationRequest->reviewer)
                                                by {{ $registrationRequest->reviewer->formattedName() }}
                                            @endif
                                        </dd>
                                    </div>
                                @endif
                                @if($registrationRequest->review_note)
                                    <div class="registration-request-note">
                                        <dt>Note</dt>
                                        <dd>{{ $registrationRequest->review_note }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if($registrationRequest->status === \App\Models\StudentRegistrationRequest::STATUS_PENDING)
                                <div class="registration-request-actions">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.registration-requests.approve', $registrationRequest) }}"
                                        onsubmit="return confirm('Approve this student registration and create the account?');"
                                    >
                                        @csrf
                                        <button type="submit" data-loading-button data-loading-text="Approving...">
                                            Approve Account
                                        </button>
                                    </form>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.registration-requests.reject', $registrationRequest) }}"
                                        class="registration-reject-form"
                                        onsubmit="return confirm('Reject this student registration request?');"
                                    >
                                        @csrf
                                        <button type="submit" class="warn" data-loading-button data-loading-text="Rejecting...">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            @elseif($registrationRequest->createdUser)
                                <div class="mini">Created account: {{ $registrationRequest->createdUser->username }}</div>
                            @endif
                        </article>
                    @endforeach
                </div>

                {{ $requests->links() }}
            @endif
        </section>
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
        <style>
            .registration-request-list {
                display: grid;
                gap: 14px;
            }

            .registration-status-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }

            .registration-status-tab {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                min-height: 40px;
                padding: 0 14px;
                border-radius: 999px;
                border: 1px solid var(--border-subtle);
                background: var(--bg-surface);
                color: var(--text-primary);
                font-size: 13px;
                font-weight: 800;
                text-decoration: none;
                transition: transform 0.15s ease, border-color 0.15s ease, background 0.15s ease;
            }

            .registration-status-tab:hover,
            .registration-status-tab.active {
                transform: translateY(-1px);
            }

            .registration-status-tab span {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 24px;
                height: 24px;
                padding: 0 7px;
                border-radius: 999px;
                background: rgba(255, 255, 255, 0.7);
            }

            .registration-status-tab.pending,
            .registration-status-tab.pending.active {
                background: var(--status-warning-bg);
                border-color: rgba(146, 64, 14, 0.18);
                color: var(--status-warning-text);
            }

            .registration-status-tab.approved,
            .registration-status-tab.approved.active {
                background: var(--status-success-bg);
                border-color: rgba(22, 101, 52, 0.18);
                color: var(--status-success-text);
            }

            .registration-status-tab.rejected,
            .registration-status-tab.rejected.active {
                background: var(--status-danger-bg);
                border-color: rgba(153, 27, 27, 0.18);
                color: var(--status-danger-text);
            }

            .registration-status-tab:not(.active) {
                opacity: 0.74;
            }

            .registration-search-form {
                display: grid;
                grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr) auto auto;
                gap: 10px;
                align-items: end;
            }

            .registration-search-form input,
            .registration-search-form select {
                width: 100%;
            }

            .registration-request-card {
                display: grid;
                gap: 16px;
                padding: 18px;
                border-radius: 18px;
                background: var(--bg-surface);
                border: 1px solid var(--border-subtle);
                box-shadow: 0 8px 20px rgba(24, 58, 99, 0.04);
            }

            .registration-request-main,
            .registration-request-actions {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 12px;
                flex-wrap: wrap;
            }

            .registration-request-details {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                gap: 12px;
                margin: 0;
            }

            .registration-request-details div {
                display: grid;
                gap: 4px;
                padding: 12px;
                border-radius: 14px;
                background: var(--bg-app);
                border: 1px solid var(--border-subtle);
            }

            .registration-request-details dt {
                color: var(--text-muted);
                font-size: 0.76rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .registration-request-details dd {
                margin: 0;
                color: var(--text-primary);
                font-weight: 700;
                line-height: 1.35;
            }

            .registration-request-note {
                grid-column: 1 / -1;
            }

            .registration-reject-form {
                display: flex;
                flex: 0 0 auto;
                gap: 10px;
            }

            .registration-request-actions button {
                min-width: 150px;
                height: 40px;
                padding: 0 16px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                font-size: 13px;
                font-weight: 700;
                line-height: 1;
                box-sizing: border-box;
            }

            .badge.pending {
                background: var(--status-warning-bg);
                color: var(--warning);
            }

            .badge.rejected {
                background: var(--status-danger-bg);
                color: var(--danger);
            }

            .badge.approved {
                background: var(--status-success-bg);
                color: var(--status-success-text);
            }

            @media (max-width: 980px) {
                .registration-search-form {
                    grid-template-columns: 1fr 1fr;
                }
            }

            @media (max-width: 720px) {
                .registration-request-actions,
                .registration-reject-form,
                .registration-search-form {
                    display: grid;
                    width: 100%;
                    grid-template-columns: 1fr;
                }

                .registration-request-actions form,
                .registration-request-actions button {
                    width: 100%;
                }
            }
        </style>
    @endpush
@endsection
