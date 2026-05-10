@php
    $currentStatus = $currentClearance
        ? ucwords(str_replace('_', ' ', $currentClearance->status))
        : 'No Clearance Yet';
@endphp

<article class="student-profile-panel">
    <header class="student-profile-hero">
        <div class="student-profile-identity">
            <div class="student-profile-avatar">
                @if($student->user->profilePhotoUrl())
                    <img src="{{ $student->user->profilePhotoUrl() }}" alt="{{ $student->displayName() }} profile picture">
                @else
                    <span>{{ strtoupper(substr($student->displayName(), 0, 1)) }}</span>
                @endif
            </div>
            <div>
                <div class="eyebrow">Student Profile</div>
                <h1>{{ $student->displayName() }}</h1>
                <p>{{ $student->student_id_number }} / {{ $student->program->code }} / {{ $student->yearLevelLabel() }}</p>
            </div>
        </div>

        <span class="badge {{ $currentClearance?->status ?? 'neutral' }}">{{ $currentStatus }}</span>
    </header>

    <section class="student-profile-grid">
        <div class="profile-card">
            <div class="eyebrow">Academic Information</div>
            <dl class="profile-facts">
                <div>
                    <dt>Student ID</dt>
                    <dd>{{ $student->student_id_number }}</dd>
                </div>
                <div>
                    <dt>Program</dt>
                    <dd>{{ $student->program->name }}</dd>
                </div>
                <div>
                    <dt>Organization</dt>
                    <dd>{{ $student->program->org_name }}</dd>
                </div>
                <div>
                    <dt>Year Level</dt>
                    <dd>{{ $student->yearLevelLabel() }}</dd>
                </div>
            </dl>
        </div>

        <div class="profile-card">
            <div class="eyebrow">Current Progress</div>
            <div class="profile-progress">
                <div class="profile-progress-meter" aria-label="Current clearance progress">
                    <span style="width: {{ $progressPercent }}%"></span>
                </div>
                <strong>{{ $progressPercent }}% complete</strong>
            </div>

            <div class="profile-counts">
                <span>Approved: {{ $stepCounts['approved'] }}</span>
                <span>Flagged: {{ $stepCounts['flagged'] }}</span>
                <span>Waiting: {{ $stepCounts['awaiting'] }}</span>
                <span>Total: {{ $stepCounts['total'] }}</span>
            </div>
        </div>
    </section>

    <section class="profile-card">
        <div class="profile-section-header">
            <div>
                <div class="eyebrow">Clearance Steps</div>
                <h2>Current clearance routing</h2>
            </div>
        </div>

        @if(!$currentClearance)
            <div class="empty-state">This student has not started a clearance for the current records yet.</div>
        @else
            <div class="profile-step-list">
                @forelse($currentClearance->steps as $step)
                    @php($lastEvent = $step->events->first())
                    <div class="profile-step-row">
                        <div>
                            <strong>{{ $step->office_label ?: $step->officeDesignation?->display_name ?: 'Office Step' }}</strong>
                            <p class="mini">
                                {{ $step->scope_label ?: 'Whole school' }}
                                @if($lastEvent?->actor)
                                    / Last actor: {{ $lastEvent->actor->formattedName() }}
                                @endif
                            </p>
                        </div>
                        <div>
                            <span class="badge {{ $step->status }}">{{ ucwords(str_replace('_', ' ', $step->status)) }}</span>
                            <p class="mini">{{ $step->signed_at?->format('M d, Y h:i A') ?? 'Not processed yet' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">No clearance steps were generated for this clearance.</div>
                @endforelse
            </div>
        @endif
    </section>

    <section class="profile-card">
        <div class="profile-section-header">
            <div>
                <div class="eyebrow">History</div>
                <h2>Clearance records</h2>
            </div>
        </div>

        <div class="profile-history-list">
            @forelse($clearances as $clearance)
                <div class="profile-history-row">
                    <div>
                        <strong>{{ $clearance->semester?->label ?? $clearance->semester_label ?? 'Semester not set' }}</strong>
                        <p class="mini">{{ $clearance->reference_number ?? 'No reference number yet' }}</p>
                    </div>
                    <div>
                        <span class="badge {{ $clearance->status }}">{{ ucwords(str_replace('_', ' ', $clearance->status)) }}</span>
                        <p class="mini">{{ $clearance->completed_at?->format('M d, Y h:i A') ?? 'Not completed' }}</p>
                    </div>
                </div>
            @empty
                <div class="empty-state">No clearance history yet.</div>
            @endforelse
        </div>
    </section>
</article>
