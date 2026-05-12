@extends('layouts.portal', [
    'title' => 'Registration Requests',
    'subtitle' => 'Approve mobile student account requests before students can sign in.',
])

@section('page')
    <div class="management-page">
        @include('admin.partials.page-feedback')

        <section class="management-header">
            <div>
                <h1>REGISTRATION REQUESTS</h1>
                <p>Mobile account sign-ups stay pending here until an admin approves or rejects them.</p>
            </div>
        </section>

        <section class="management-card">
            <div class="management-card-header">
                <div>
                    <h2>Review Queue</h2>
                    <p class="management-card-kicker">Check student identity, program, and year level before creating the account.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.registration-requests.index') }}" class="management-filter-grid">
                <label>
                    Status
                    <select name="status">
                        <option value="pending" @selected($status === 'pending')>Pending</option>
                        <option value="approved" @selected($status === 'approved')>Approved</option>
                        <option value="rejected" @selected($status === 'rejected')>Rejected</option>
                    </select>
                </label>

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

                <button type="submit" class="management-primary-action">Apply Filters</button>
                <a href="{{ route('admin.registration-requests.index') }}" class="button secondary management-secondary-action">Reset</a>
            </form>

            <div class="management-summary-strip">
                <span class="management-summary-pill">Pending: {{ $pendingCount }}</span>
                <span class="management-summary-pill">Approved: {{ $approvedCount }}</span>
                <span class="management-summary-pill">Rejected: {{ $rejectedCount }}</span>
            </div>

            @if($requests->isEmpty())
                <div class="empty-state">
                    No {{ $status }} registration requests found.
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

            .registration-request-card {
                display: grid;
                gap: 16px;
                padding: 18px;
                border-radius: 18px;
                background: #ffffff;
                border: 1px solid #e4dacd;
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
                background: #f8f4ea;
                border: 1px solid #ebe2d4;
            }

            .registration-request-details dt {
                color: #667085;
                font-size: 0.76rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .registration-request-details dd {
                margin: 0;
                color: #183a63;
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
                background: #fff3d9;
                color: var(--warning);
            }

            .badge.rejected {
                background: #fbe2dc;
                color: var(--danger);
            }

            @media (max-width: 720px) {
                .registration-request-actions,
                .registration-reject-form {
                    display: grid;
                    width: 100%;
                }

                .registration-request-actions form,
                .registration-request-actions button {
                    width: 100%;
                }
            }
        </style>
    @endpush
@endsection
