@extends('layouts.portal', ['title' => 'Office Dashboard'])

@section('page')
    @php
        $validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all());
        $currentOfficeUser = auth()->user();
        $currentOfficerName = $currentOfficeUser?->officeAccount?->display_name
            ?? $currentOfficeUser?->formattedName()
            ?? $currentOfficeUser?->name
            ?? 'Office User';
        $currentOfficerPhoto = $currentOfficeUser?->profilePhotoUrl();
    @endphp

    <div class="topbar">
        <div class="topbar-left">
            <img src="{{ asset('images/lnu-logo.png') }}" alt="LNU Logo" class="topbar-logo">

            <div>
                <h1>{{ $dashboardTitle }}</h1>

                @if($hasActiveDesignation)
                    <p>
                        Current designation{{ $officeDesignations->count() === 1 ? '' : 's' }}:
                        {{ $officeDesignations->pluck('display_name')->implode(' | ') }}
                    </p>
                @else
                    <p>No active designation assigned</p>
                @endif
            </div>
        </div>

        <div class="toolbar office-user-toolbar">
            <details class="office-user-menu">
                <summary>
                    <span class="office-user-name">{{ $currentOfficerName }}</span>
                    <span class="office-user-avatar">
                        @if($currentOfficerPhoto)
                            <img src="{{ $currentOfficerPhoto }}" alt="{{ $currentOfficerName }} profile picture">
                        @else
                            <span>{{ strtoupper(substr($currentOfficerName, 0, 1)) }}</span>
                        @endif
                    </span>
                </summary>

                <div class="office-user-dropdown">
                    <div>
                        <strong>{{ $currentOfficerName }}</strong>
                        <span>{{ $currentOfficeUser?->username }}</span>
                    </div>

                    <form method="POST" action="{{ route('portal.logout') }}">
                        @csrf
                        <button type="submit" class="topbar-action">Sign Out</button>
                    </form>
                </div>
            </details>
        </div>
    </div>

    <div class="content stack office-dashboard">
        @if(session('success'))
            <div class="callout success">{{ session('success') }}</div>
        @endif

        @if(session('info'))
            <div class="callout success">{{ session('info') }}</div>
        @endif

        @if(session('error'))
            <div class="callout error">{{ session('error') }}</div>
        @endif

        @if($validationErrors->isNotEmpty())
            <div class="callout error">{{ $validationErrors->first() }}</div>
        @endif

        @if(!$hasActiveDesignation)
            <section class="office-empty-dashboard">
                <div class="eyebrow">Assignment Status</div>
                <div class="office-empty-dashboard-body">
                    <h2>No Active Designation Assigned</h2>
                    <p>Your account currently has no active designation assignment.</p>
                    <p>No clearance items are currently assigned to your designation.</p>
                    <p>Please contact the super admin to assign your designation.</p>
                </div>
            </section>
        @else
            <nav class="office-tabs" aria-label="Office dashboard sections">
                <a href="{{ route('office.dashboard', ['tab' => 'active']) }}" class="office-tab {{ $tab === 'active' ? 'active' : '' }}">
                    Active Queue
                    <span>{{ $pendingCount }}</span>
                </a>
                <a href="{{ route('office.dashboard', ['tab' => 'archive']) }}" class="office-tab {{ $tab === 'archive' ? 'active' : '' }}">
                    Completed Clearance Archive
                    <span>{{ $archiveCount }}</span>
                </a>
            </nav>

            @if($tab === 'active')
                <section class="office-panel">
                    <div class="office-section-header">
                        <div>
                            <div class="eyebrow">Active Queue</div>
                            <h2>Clearance steps awaiting your action</h2>
                            <p class="muted">Only students ready for your current designation appear here. VPSD receives students after every other office signs.</p>
                        </div>

                        <button type="button" class="button workload-report-trigger" id="openWorkloadReport">
                            Progress Report
                        </button>
                    </div>

                    <form method="GET" action="{{ route('office.dashboard') }}" class="office-queue-filter">
                        <input type="hidden" name="tab" value="active">

                        <label>
                            <span>Find</span>
                            <input
                                type="search"
                                name="pending_search"
                                value="{{ $pendingSearch }}"
                                placeholder="Name, student ID, program"
                                maxlength="120"
                            >
                        </label>

                        @if($queueScope['show_program_filter'])
                            <label>
                                <span>Program</span>
                                <select name="pending_program">
                                    <option value="">All programs</option>
                                    @foreach($programOptions as $program)
                                        <option value="{{ $program->id }}" @selected((int) $pendingProgram === (int) $program->id)>
                                            {{ $program->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @else
                            <div class="office-fixed-filter">
                                <span>Program</span>
                                <strong>{{ $queueScope['fixed_program_code'] ?? 'Scoped' }}</strong>
                            </div>
                        @endif

                        @if($queueScope['show_year_filter'])
                            <label>
                                <span>Year Level</span>
                                <select name="pending_year">
                                    <option value="">All years</option>
                                    @foreach($yearLevelOptions as $yearValue => $yearLabel)
                                        <option value="{{ $yearValue }}" @selected((int) $pendingYear === (int) $yearValue)>
                                            {{ $yearLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @else
                            <div class="office-fixed-filter">
                                <span>Year Level</span>
                                <strong>{{ $queueScope['fixed_year_label'] ?? 'Scoped' }}</strong>
                            </div>
                        @endif

                        <label>
                            <span>Section</span>
                            <select name="pending_section">
                                <option value="">All sections</option>
                                @foreach($sectionOptions as $section)
                                    <option value="{{ $section }}" @selected((string) $pendingSection === (string) $section)>
                                        {{ $section }}
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <span>Sort</span>
                            <select name="pending_sort">
                                <option value="waiting_desc" @selected($pendingSort === 'waiting_desc')>Newest waiting</option>
                                <option value="waiting_asc" @selected($pendingSort === 'waiting_asc')>Oldest waiting</option>
                                <option value="student_asc" @selected($pendingSort === 'student_asc')>Student name</option>
                                <option value="student_id_asc" @selected($pendingSort === 'student_id_asc')>Student ID</option>
                                <option value="program_asc" @selected($pendingSort === 'program_asc')>Program</option>
                                <option value="year_asc" @selected($pendingSort === 'year_asc')>Year level</option>
                                <option value="section_asc" @selected($pendingSort === 'section_asc')>Section</option>
                            </select>
                        </label>

                        <button type="submit">Apply</button>
                        <a href="{{ route('office.dashboard', ['tab' => 'active']) }}" class="button ghost">Reset</a>
                    </form>

                    <div class="list office-list">
                        @forelse($pendingSteps as $step)
                            @php
                                $student = $step->clearance->student;
                                $studentName = $student?->displayName() ?: 'Student record unavailable';
                                $studentId = $student?->student_id_number ?: 'No ID';
                                $programCode = $student?->program?->code ?: 'No program';
                                $yearLevel = $student?->yearLevelLabel() ?: 'No year level';
                                $section = $student?->sectionLabel() ?: 'No section';
                                $studentMeta = $studentId . ' | ' . $programCode . ' | ' . $yearLevel . ' | ' . $section;
                                $studentPhoto = $student?->user?->profilePhotoUrl();
                                $clearanceSteps = $step->clearance?->steps ?? collect();
                                $totalSteps = $clearanceSteps->count();
                                $approvedSteps = $clearanceSteps->where('status', \App\Models\ClearanceStep::STATUS_APPROVED)->count();
                                $waitingSteps = $clearanceSteps->where('status', \App\Models\ClearanceStep::STATUS_AWAITING_ACTION)->count();
                                $progressPercent = $totalSteps > 0 ? (int) floor(($approvedSteps / $totalSteps) * 100) : 0;
                                $progressColor = "color-mix(in srgb, #166534 {$progressPercent}%, #991B1B)";
                            @endphp

                            <div class="record office-record pending-record">
                                <div class="record-top">
                                    <div class="office-student-identity">
                                        <div class="office-student-avatar">
                                            @if($studentPhoto)
                                                <img src="{{ $studentPhoto }}" alt="{{ $studentName }} profile picture">
                                            @else
                                                <span>{{ strtoupper(substr($studentName, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <strong class="record-name">{{ $studentName }}</strong>
                                            <div class="mini">
                                                {{ $studentMeta }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="record-actions">
                                        <span class="badge awaiting_action">Awaiting Action</span>

                                        <div class="office-action-buttons">
                                            <a
                                                href="{{ $student ? route('office.students.show', $student) : '#' }}"
                                                class="button profile-link"
                                                data-office-profile-link
                                                data-student-profile-url="{{ $student ? route('office.students.show', [$student, 'partial' => 1]) : '' }}"
                                                aria-disabled="{{ $student ? 'false' : 'true' }}"
                                            >
                                                View Profile
                                            </a>

                                            <button
                                                type="button"
                                                class="button approve-trigger"
                                                data-step-id="{{ $step->id }}"
                                                data-student-name="{{ $studentName }}"
                                                data-step-action="{{ route('office.steps.process', $step) }}"
                                            >
                                                Approve
                                            </button>

                                            <button
                                                type="button"
                                                class="button warn flag-trigger"
                                                data-step-id="{{ $step->id }}"
                                                data-student-name="{{ $studentName }}"
                                                data-step-action="{{ route('office.steps.process', $step) }}"
                                            >
                                                Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="record-meta">
                                    <div
                                        class="clearance-mini-progress"
                                        title="{{ $approvedSteps }} approved, {{ $waitingSteps }} waiting, {{ $totalSteps }} total offices"
                                    >
                                        <div class="clearance-mini-progress-top">
                                            <strong>Progress</strong>
                                            <span>{{ $approvedSteps }}/{{ $totalSteps }} signed</span>
                                        </div>
                                        <div class="clearance-mini-progress-track" aria-hidden="true">
                                            <span style="width: {{ $progressPercent }}%; --progress-color: {{ $progressColor }}"></span>
                                        </div>
                                    </div>

                                    <p class="mini">
                                        <strong>Designation:</strong>
                                        {{ $step->office_label ?: '-' }}
                                    </p>

                                    <p class="mini">
                                        <strong>Clearance status:</strong>
                                        {{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="record office-empty-state">
                                <p class="muted" style="margin: 0;">No routed students are waiting on your current designation set right now.</p>
                            </div>
                        @endforelse
                    </div>

                    @if(method_exists($pendingSteps, 'hasPages') && $pendingSteps->hasPages())
                        <nav class="pagination-wrapper office-simple-pagination" aria-label="Active queue pagination">
                            @if($pendingSteps->onFirstPage())
                                <span class="button ghost is-disabled" aria-disabled="true">Previous</span>
                            @else
                                <a class="button ghost" href="{{ $pendingSteps->previousPageUrl() }}">Previous</a>
                            @endif

                            <span class="office-page-indicator">Page {{ $pendingSteps->currentPage() }}</span>

                            @if($pendingSteps->hasMorePages())
                                <a class="button ghost" href="{{ $pendingSteps->nextPageUrl() }}">Next</a>
                            @else
                                <span class="button ghost is-disabled" aria-disabled="true">Next</span>
                            @endif
                        </nav>
                    @endif
                </section>
            @else
                <section class="office-panel">
                    <div class="office-section-header archive-header">
                        <div>
                            <div class="eyebrow">Archive</div>
                            <h2>Completed clearance signs</h2>
                            <p class="muted">Approved and rejected signs are separated from active work so the queue stays focused.</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('office.dashboard') }}" class="office-archive-filter">
                        <input type="hidden" name="tab" value="archive">

                        <label>
                            <span>Find</span>
                            <input
                                type="search"
                                name="archive_search"
                                value="{{ $archiveSearch }}"
                                placeholder="Name, student ID, program, note, reference"
                                maxlength="120"
                            >
                        </label>

                        <label>
                            <span>Status</span>
                            <select name="archive_status">
                                <option value="">All</option>
                                <option value="approved" @selected($archiveStatus === 'approved')>Approved</option>
                                <option value="flagged" @selected($archiveStatus === 'flagged')>Rejected</option>
                            </select>
                        </label>

                        <label>
                            <span>Sort</span>
                            <select name="archive_sort">
                                <option value="processed_desc" @selected($archiveSort === 'processed_desc')>Newest processed</option>
                                <option value="processed_asc" @selected($archiveSort === 'processed_asc')>Oldest processed</option>
                                <option value="student_asc" @selected($archiveSort === 'student_asc')>Student name</option>
                                <option value="student_id_asc" @selected($archiveSort === 'student_id_asc')>Student ID</option>
                                <option value="program_asc" @selected($archiveSort === 'program_asc')>Program</option>
                            </select>
                        </label>

                        <button type="submit">Apply</button>
                        <a href="{{ route('office.dashboard', ['tab' => 'archive']) }}" class="button ghost">Reset</a>
                    </form>

                    @if($archiveLoadError)
                        <div class="callout error">{{ $archiveLoadError }}</div>
                    @endif

                    <div class="list office-list">
                        @forelse($archiveSteps as $step)
                            <div class="record office-record processed-record">
                                <div class="record-top">
                                    <div class="office-student-identity">
                                        <div class="office-student-avatar">
                                            @if($step['student_photo'])
                                                <img src="{{ $step['student_photo'] }}" alt="{{ $step['student_name'] }} profile picture">
                                            @else
                                                <span>{{ $step['student_initial'] }}</span>
                                            @endif
                                        </div>
                                        <div>
                                            <strong class="record-name">{{ $step['student_name'] }}</strong>
                                            <div class="mini">
                                                {{ $step['student_meta'] }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="record-actions">
                                        <span class="badge {{ $step['status'] }}">
                                            {{ $step['status_label'] }}
                                        </span>

                                        <div class="office-action-buttons">
                                            <a
                                                href="{{ $step['student_profile_url'] }}"
                                                class="button profile-link"
                                                data-office-profile-link
                                                data-student-profile-url="{{ $step['student_profile_url'] ? $step['student_profile_url'] . (str_contains($step['student_profile_url'], '?') ? '&' : '?') . 'partial=1' : '' }}"
                                                aria-disabled="{{ $step['student_profile_disabled'] ? 'true' : 'false' }}"
                                            >
                                                View Profile
                                            </a>

                                            @if($step['status'] === 'approved')
                                                <button
                                                    type="button"
                                                    class="button ghost undo-trigger"
                                                    data-step-id="{{ $step['id'] }}"
                                                    data-student-name="{{ $step['student_name'] }}"
                                                    data-step-action="{{ $step['process_url'] }}"
                                                >
                                                    Undo Approval
                                                </button>
                                            @elseif($step['status'] === 'flagged')
                                                <button
                                                    type="button"
                                                    class="button ghost undo-flag-trigger"
                                                    data-step-id="{{ $step['id'] }}"
                                                    data-student-name="{{ $step['student_name'] }}"
                                                    data-step-action="{{ $step['process_url'] }}"
                                                >
                                                    Undo Rejection
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="record-meta">
                                    <p class="mini">
                                        <strong>Designation:</strong>
                                        {{ $step['office_label'] }}
                                    </p>

                                    <p class="mini">
                                        <strong>Processed:</strong>
                                        {{ $step['processed_label'] }}
                                    </p>

                                    <p class="mini">
                                        <strong>{{ $step['meta_note_label'] }}:</strong>
                                        {{ $step['remarks'] }}
                                    </p>

                                    <p class="mini">
                                        <strong>Student clearance status:</strong>
                                        {{ $step['clearance_status'] }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="record office-empty-state">
                                <p class="muted" style="margin: 0;">No archived signs match the selected filters.</p>
                            </div>
                        @endforelse
                    </div>

                    @if(method_exists($archiveSteps, 'hasPages') && $archiveSteps->hasPages())
                        <nav class="pagination-wrapper office-simple-pagination" aria-label="Archive pagination">
                            @if($archiveSteps->onFirstPage())
                                <span class="button ghost is-disabled" aria-disabled="true">Previous</span>
                            @else
                                <a class="button ghost" href="{{ $archiveSteps->previousPageUrl() }}">Previous</a>
                            @endif

                            <span class="office-page-indicator">Page {{ $archiveSteps->currentPage() }}</span>

                            @if($archiveSteps->hasMorePages())
                                <a class="button ghost" href="{{ $archiveSteps->nextPageUrl() }}">Next</a>
                            @else
                                <span class="button ghost is-disabled" aria-disabled="true">Next</span>
                            @endif
                        </nav>
                    @endif
                </section>
            @endif
        @endif
    </div>

    <div class="office-modal-backdrop" id="officeDetailModal" hidden>
        <div class="office-modal" role="dialog" aria-modal="true" aria-labelledby="officeModalTitle">
            <div class="office-modal-header">
                <div>
                    <h2 id="officeModalTitle">Student Name</h2>
                    <p id="officeModalMeta">Student meta</p>
                </div>

                <button type="button" class="office-modal-close" id="closeOfficeModal" aria-label="Close modal">
                    &times;
                </button>
            </div>

            <div class="office-modal-body">
                <div class="office-status-row">
                    <span class="badge awaiting_action" id="modalStepStatus">Awaiting Action</span>
                    <span class="badge neutral" id="modalClearanceStatus">In Progress</span>
                </div>

                <div class="office-detail-block">
                    <p><strong>Designation:</strong> <span id="modalDesignation">-</span></p>
                </div>

                <div class="office-detail-block">
                    <p><strong>Last Processed:</strong> <span id="modalLastProcessed">-</span></p>
                </div>

                <div class="office-detail-block">
                    <label class="office-label" id="modalNoteLabel">Previous Office Note</label>
                    <div class="office-note-box" id="modalPreviousNote">-</div>
                </div>

                <div class="office-modal-actions">
                    <button type="button" class="office-cancel-link" id="cancelOfficeModal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="office-modal-backdrop office-profile-backdrop" id="officeStudentProfileModal" hidden>
        <div class="office-profile-modal" role="dialog" aria-modal="true" aria-labelledby="officeStudentProfileTitle">
            <div class="office-modal-header">
                <div>
                    <div class="eyebrow">Student Profile</div>
                    <h2 id="officeStudentProfileTitle">Student details</h2>
                </div>

                <button type="button" class="office-modal-close" id="closeOfficeStudentProfile" aria-label="Close profile">
                    &times;
                </button>
            </div>

            <div class="office-profile-modal-body" id="officeStudentProfileBody">
                <div class="empty-state">Loading student profile...</div>
            </div>

            <div class="office-profile-modal-footer">
                <button type="button" class="office-cancel-link" id="cancelOfficeStudentProfile">Close</button>
            </div>
        </div>
    </div>

    <div class="office-modal-backdrop office-profile-backdrop" id="workloadReportModal" hidden>
        <div class="office-profile-modal workload-report-modal" role="dialog" aria-modal="true" aria-labelledby="workloadReportTitle">
            <div class="office-modal-header">
                <div>
                    <div class="eyebrow">Progress Report</div>
                    <h2 id="workloadReportTitle">Remaining workload</h2>
                </div>

                <button type="button" class="office-modal-close" id="closeWorkloadReport" aria-label="Close progress report">
                    &times;
                </button>
            </div>

            <div class="office-profile-modal-body">
                <div class="workload-report-summary">
                    <div>
                        <span>Active Queue</span>
                        <strong>{{ number_format($workloadReport['total']) }}</strong>
                        <p>students still waiting for this designation set</p>
                    </div>
                </div>

                @php
                    $reportSections = collect([
                        $queueScope['show_program_filter'] ? [
                            'title' => 'By Program',
                            'items' => $workloadReport['by_program'],
                        ] : null,
                        $queueScope['show_year_filter'] ? [
                            'title' => 'By Year Level',
                            'items' => $workloadReport['by_year'],
                        ] : null,
                        [
                            'title' => 'By Section',
                            'items' => $workloadReport['by_section'],
                        ],
                    ])->filter();
                @endphp

                <div class="workload-report-grid">
                    @foreach($reportSections as $reportSection)
                        @php
                            $items = collect($reportSection['items']);
                            $maxTotal = max((int) $items->max('total'), 1);
                        @endphp

                        <section class="workload-report-card">
                            <div class="workload-report-card-header">
                                <h3>{{ $reportSection['title'] }}</h3>
                                <span>{{ number_format($items->sum('total')) }} waiting</span>
                            </div>

                            <div class="workload-report-bars">
                                @forelse($items as $item)
                                    @php
                                        $barPercent = max(8, (int) round(((int) $item->total / $maxTotal) * 100));
                                    @endphp
                                    <div class="workload-report-row">
                                        <div class="workload-report-label">
                                            <strong>{{ $item->label ?? 'No label' }}</strong>
                                            <span>{{ number_format((int) $item->total) }}</span>
                                        </div>
                                        <div class="workload-report-track" aria-hidden="true">
                                            <span style="width: {{ $barPercent }}%"></span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state">No waiting students in this group.</div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>

            <div class="office-profile-modal-footer">
                <button type="button" class="office-cancel-link" id="cancelWorkloadReport">Close</button>
            </div>
        </div>
    </div>

        <div class="office-modal-backdrop" id="approveModal" hidden>
        <div class="office-modal" role="dialog" aria-modal="true" aria-labelledby="approveModalTitle">
            <div class="office-modal-header">
                <div>
                    <h2 id="approveModalTitle">Confirm Approval</h2>
                    <p id="approveModalMeta">Student Name</p>
                </div>

                <button type="button" class="office-modal-close" id="closeApproveModal" aria-label="Close modal">
                    &times;
                </button>
            </div>

            <div class="office-modal-body">
                <p>Are you sure you want to approve this clearance step?</p>

                <form method="POST" id="approveForm">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="confirm_action" value="approve">
                    <input type="hidden" name="return_tab" value="active">

                    <div class="office-modal-actions">
                        <button type="submit">Confirm Approval</button>
                    </div>

                    <button type="button" class="office-cancel-link" id="cancelApproveModal">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <div class="office-modal-backdrop" id="flagModal" hidden>
        <div class="office-modal" role="dialog" aria-modal="true" aria-labelledby="flagModalTitle">
            <div class="office-modal-header">
                <div>
                    <h2 id="flagModalTitle">Reject Clearance Step</h2>
                    <p id="flagModalMeta">Student Name</p>
                </div>

                <button type="button" class="office-modal-close" id="closeFlagModal" aria-label="Close modal">
                    &times;
                </button>
            </div>

            <div class="office-modal-body">
                <form method="POST" id="flagForm">
                    @csrf
                    <input type="hidden" name="action" value="flag">
                    <input type="hidden" name="step_id" id="flagStepId" value="{{ old('step_id') }}">
                    <input type="hidden" name="return_tab" value="active">

                    <label class="office-label" for="flagRemarks">Reject Reason</label>
                    <p class="mini" style="margin: -8px 0 0;">This field is required.</p>

                    <textarea
                        id="flagRemarks"
                        name="remarks"
                        rows="5"
                        placeholder="Enter the reason for rejecting this clearance step"
                        required
                    >{{ old('remarks') }}</textarea>
                    <x-field-error field="remarks" bag="officeProcess" />

                    <div class="office-modal-actions">
                        <button type="submit" class="warn">Submit Rejection</button>
                    </div>

                    <button type="button" class="office-cancel-link" id="cancelFlagModal">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <div class="office-modal-backdrop" id="undoModal" hidden>
        <div class="office-modal" role="dialog" aria-modal="true" aria-labelledby="undoModalTitle">
            <div class="office-modal-header">
                <div>
                    <h2 id="undoModalTitle">Undo Approval</h2>
                    <p id="undoModalMeta">Student Name</p>
                </div>

                <button type="button" class="office-modal-close" id="closeUndoModal" aria-label="Close modal">
                    &times;
                </button>
            </div>

            <div class="office-modal-body">
                <p>Are you sure you want to undo this approval?</p>

                <form method="POST" id="undoForm">
                    @csrf
                    <input type="hidden" name="action" value="undo_approval">
                    <input type="hidden" name="confirm_action" value="undo_approval">
                    <input type="hidden" name="return_tab" value="archive">

                    <div class="office-modal-actions">
                        <button type="submit" class="warn">Confirm Undo</button>
                    </div>

                    <button type="button" class="office-cancel-link" id="cancelUndoModal">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <div class="office-modal-backdrop" id="undoFlagModal" hidden>
        <div class="office-modal" role="dialog" aria-modal="true" aria-labelledby="undoFlagModalTitle">
            <div class="office-modal-header">
                <div>
                    <h2 id="undoFlagModalTitle">Undo Rejection</h2>
                    <p id="undoFlagModalMeta">Student Name</p>
                </div>

                <button type="button" class="office-modal-close" id="closeUndoFlagModal" aria-label="Close modal">
                    &times;
                </button>
            </div>

            <div class="office-modal-body">
                <p>Are you sure you want to remove this rejection and return the step to awaiting action?</p>

                <form method="POST" id="undoFlagForm">
                    @csrf
                    <input type="hidden" name="action" value="undo_flag">
                    <input type="hidden" name="confirm_action" value="undo_flag">
                    <input type="hidden" name="return_tab" value="archive">

                    <div class="office-modal-actions">
                        <button type="submit" class="warn">Confirm Undo Rejection</button>
                    </div>

                    <button type="button" class="office-cancel-link" id="cancelUndoFlagModal">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    @include('students.partials.profile-styles')

    <style>
        .office-modal-close {
            display: none !important;
        }

        /* Office dashboard 60/30/10 balance: warm surface, navy structure, gold accent. */
        :root {
            --office-major: #F7F5EF;
            --office-secondary: #0E2A47;
            --office-accent: #D4A53A;
            --office-surface: #FFFFFF;
            --office-line: #E7E3D8;
        }
        
        .topbar {
            margin: -28px -32px 0;
            border-radius: 0;
        }

        .topbar {
            background: linear-gradient(
                135deg,
                #0e2742 0%,
                #16385f 60%,
                #1f4f85 100%
            );
            color: white;
        }

        .topbar h1,
        .topbar p,
        .topbar .eyebrow {
            color: #fff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.22);
        }

        .topbar p {
            color: rgba(255, 255, 255, 0.84);
        }

        .office-dashboard {
            gap: 18px;
            width: 100%;
            margin: 0;
            padding: 24px 30px 36px;
            background: transparent;
            border-radius: 0;
        }

        .office-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 6px;
            border: 1px solid var(--office-line);
            border-radius: 18px;
            background: rgba(14, 42, 71, 0.06);
        }

        .office-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 14px;
            color: var(--navy);
            font-weight: 800;
            text-decoration: none;
            transition: background 0.15s ease, box-shadow 0.15s ease;
        }

        .office-tab span {
            min-width: 28px;
            padding: 4px 8px;
            border-radius: 999px;
            background: rgba(22, 56, 95, 0.1);
            text-align: center;
            font-size: 0.82rem;
        }

        .office-tab.active {
            background: var(--office-secondary);
            color: white;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.12);
        }

        .office-tab.active span {
            background: rgba(212, 165, 58, 0.22);
            color: #fff4cc;
        }

        .office-panel {
            padding: 0;
            border: 0;
            background: transparent;
        }

        .office-section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            margin-bottom: 18px;
        }

        .office-section-header h2 {
            margin: 4px 0 6px;
            color: var(--navy-deep);
            font-size: 1.55rem;
            line-height: 1.15;
        }

        .office-list {
            display: grid;
            gap: 14px;
        }

        .office-archive-filter,
        .office-queue-filter {
            display: grid;
            grid-template-columns: minmax(200px, 1fr) repeat(4, minmax(110px, 150px)) auto auto;
            gap: 10px;
            align-items: end;
            margin: 0 0 18px;
            padding: 12px;
            border: 1px solid var(--office-line);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.72);
        }

        .office-archive-filter {
            grid-template-columns: minmax(220px, 1fr) minmax(150px, 180px) minmax(170px, 210px) auto auto;
        }

        .office-archive-filter label,
        .office-queue-filter label,
        .office-fixed-filter {
            display: grid;
            gap: 6px;
            font-weight: 700;
            color: var(--navy-deep);
        }

        .office-archive-filter span,
        .office-queue-filter span,
        .office-fixed-filter span {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .office-fixed-filter {
            min-height: 38px;
            align-self: stretch;
            justify-content: end;
            padding: 7px 11px;
            border: 1px dashed rgba(14, 42, 71, 0.2);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.58);
        }

        .office-fixed-filter strong {
            color: var(--navy-deep);
            font-size: 0.94rem;
        }

        .office-archive-filter input,
        .office-archive-filter select,
        .office-queue-filter input,
        .office-queue-filter select {
            width: 100%;
            min-height: 38px;
            border: 1px solid var(--office-line);
            border-radius: 12px;
            padding: 8px 11px;
            background: white;
        }

        .office-record {
            border-radius: 16px;
            padding: 14px 16px;
            border: 1px solid var(--office-line);
            background: rgba(255, 255, 255, 0.78);
            box-shadow: none;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .office-record:hover {
            border-color: rgba(212, 165, 58, 0.62);
            background: var(--office-surface);
            transform: none;
            box-shadow: none;
        }

        .record-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
        }

        .record-name {
            display: block;
            font-size: 1rem;
            margin-bottom: 3px;
            color: var(--navy-deep);
            overflow-wrap: anywhere;
        }

        .office-student-identity {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .office-student-identity > div:last-child {
            min-width: 0;
        }

        .office-student-avatar {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: linear-gradient(135deg, #173c66 0%, #27588f 100%);
            color: #fff;
            font-weight: 900;
            border: 2px solid #fff;
            box-shadow: 0 8px 16px rgba(24, 58, 99, 0.12);
        }

        .office-student-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .record-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
            flex-shrink: 0;
        }

        .office-action-buttons {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .record-meta {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--office-line);
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px 14px;
        }

        .record-meta p {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .clearance-mini-progress {
            grid-column: 1 / -1;
            display: grid;
            gap: 6px;
        }

        .clearance-mini-progress-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .clearance-mini-progress-top strong {
            color: var(--navy-deep);
        }

        .clearance-mini-progress-track {
            height: 7px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(14, 42, 71, 0.1);
        }

        .clearance-mini-progress-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--progress-color, #D4A53A);
            transition: width 0.18s ease, background-color 0.18s ease;
        }

        .button.ghost,
        .detail-trigger,
        .profile-link,
        .workload-report-trigger {
            background: white;
            border: 1px solid rgba(22, 56, 95, 0.22);
            color: var(--navy);
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(16, 24, 40, 0.04);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .button.ghost:hover,
        .detail-trigger:hover,
        .profile-link:hover,
        .workload-report-trigger:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(16, 24, 40, 0.08);
            border-color: rgba(22, 56, 95, 0.35);
        }

        .workload-report-trigger {
            white-space: nowrap;
        }

        .office-simple-pagination {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 16px;
        }

        .office-page-indicator {
            color: var(--muted);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .button.ghost.is-disabled,
        .button.ghost.is-disabled:hover {
            pointer-events: none;
            opacity: 0.48;
            transform: none;
            box-shadow: none;
        }

        .profile-link {
            background: #edf4ff;
            border-color: rgba(22, 56, 95, 0.18);
        }

        .profile-link[aria-disabled="true"] {
            pointer-events: none;
            opacity: 0.55;
        }

        .approve-trigger {
            box-shadow: 0 6px 14px rgba(22, 56, 95, 0.14);
        }

        .flag-trigger,
        .warn {
            box-shadow: 0 6px 14px rgba(181, 68, 44, 0.14);
        }

        .office-empty-state {
            padding: 22px 20px;
            border-radius: 18px;
            background: #f8f7f3;
            border: 1px dashed #d6ccbd;
            box-shadow: none;
        }

        .office-empty-dashboard {
            max-width: 780px;
            margin: 0;
            padding: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .office-empty-dashboard-body {
            text-align: left;
            padding: 0;
            border-top: 0;
        }

        .office-empty-dashboard-body h2 {
            margin: 0 0 16px;
            font-size: 1.9rem;
            line-height: 1.2;
            color: var(--navy-deep);
        }

        .office-empty-dashboard-body p {
            margin: 0 0 12px;
            color: #4b5565;
            font-size: 1rem;
        }

        .office-empty-dashboard-body p:last-child {
            margin-bottom: 0;
        }

        .badge.neutral {
            background: #ece7dc;
            color: #3d3a36;
        }

        .office-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(3px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 1000;
        }

        .office-modal-backdrop[hidden] {
            display: none !important;
        }

        .office-modal {
            width: min(100%, 520px);
            background: #fcfbf7;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .office-profile-modal {
            width: min(880px, 100%);
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            background: var(--bg-surface);
            border-radius: 0.75rem;
            box-shadow: 0 24px 70px rgba(14, 39, 66, 0.26);
            border: 1px solid rgba(255, 255, 255, 0.62);
        }

        .office-profile-modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            padding: 1.5rem;
            background: var(--bg-app);
        }

        .office-profile-modal-footer {
            flex: 0 0 auto;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            background: var(--bg-surface);
            border-top: 1px solid var(--border-subtle);
        }

        .workload-report-modal {
            max-width: 880px;
        }

        .workload-report-summary {
            margin-bottom: 1rem;
        }

        .workload-report-summary > div {
            padding: 1.25rem;
            border: 1px solid var(--border-subtle);
            border-radius: 1rem;
            background:
                radial-gradient(circle at top right, rgba(212, 165, 58, 0.18), transparent 34%),
                #fff;
        }

        .workload-report-summary span,
        .workload-report-card-header span {
            display: block;
            color: var(--text-muted);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .workload-report-summary strong {
            display: block;
            margin: 0.15rem 0;
            color: var(--brand-navy);
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1;
        }

        .workload-report-summary p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .workload-report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }

        .workload-report-card {
            display: grid;
            gap: 0.9rem;
            padding: 1rem;
            border: 1px solid var(--border-subtle);
            border-radius: 1rem;
            background: #fff;
        }

        .workload-report-card-header {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .workload-report-card-header h3 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .workload-report-bars {
            display: grid;
            gap: 0.75rem;
        }

        .workload-report-row {
            display: grid;
            gap: 0.35rem;
        }

        .workload-report-label {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            color: var(--text-primary);
            font-size: 0.88rem;
        }

        .workload-report-label span {
            color: var(--text-muted);
            font-weight: 800;
        }

        .workload-report-track {
            height: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(14, 42, 71, 0.09);
        }

        .workload-report-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--brand-navy), var(--brand-gold));
        }

        .office-modal-header {
            background: linear-gradient(135deg, #16385f, #254f82);
            color: white;
            padding: 24px 28px;
            display: flex;
            justify-content: flex-start; /* CHANGE THIS */
            align-items: flex-start;
            gap: 12px;
        }

        .office-modal-header h2 {
            margin: 0 0 6px;
            font-size: 1.8rem;
            line-height: 1.1;
        }

        .office-modal-header p {
            margin: 0;
            opacity: 0.92;
        }

        .office-modal-close {
            border: 1px solid rgba(255,255,255,0.55);
            background: transparent;
            color: white;
            width: 44px;
            height: 44px;
            border-radius: 999px;
            font-size: 1.75rem;
            line-height: 1;
            cursor: pointer;
        }

        .office-profile-modal .office-modal-header {
            position: sticky;
            top: 0;
            z-index: 2;
            flex: 0 0 auto;
            justify-content: space-between;
            gap: 10px;
            padding: 1rem 1.5rem;
            background: var(--bg-surface);
            color: var(--text-primary);
            border-bottom: 1px solid var(--border-subtle);
        }

        .office-profile-modal .office-modal-header h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.125rem;
            line-height: 1.25;
        }

        .office-profile-modal .office-modal-header .eyebrow {
            color: var(--text-muted);
        }

        .office-profile-modal .office-cancel-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
            padding: 0 16px;
            border-radius: 0.625rem;
            border: 1px solid var(--border-subtle);
            background: var(--bg-surface);
            color: var(--text-primary);
            font-size: 0.875rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .office-profile-modal .office-cancel-link:hover {
            background: var(--bg-app);
        }

        .office-modal-body {
            padding: 22px 24px 24px;
            display: grid;
            gap: 18px;
        }

        .office-status-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .office-detail-block {
            display: grid;
            gap: 8px;
            padding-top: 4px;
            border-top: 1px solid #e5dfd4;
        }

        .office-detail-block p {
            margin: 0;
        }

        .office-label {
            display: block;
            font-weight: 700;
            color: #2a2a2a;
        }

        .office-note-box {
            border: 1px solid #ddd6ca;
            border-radius: 14px;
            background: white;
            min-height: 54px;
            padding: 14px 16px;
            color: #444;
        }

        #flagForm textarea {
            width: 100%;
            resize: vertical;
            min-height: 140px;
        }

        .office-modal-actions {
            display: flex;
            gap: 14px;
            margin-top: 14px;
        }

        .office-modal-actions > button {
            flex: 1;
        }

        .office-cancel-link {
            display: block;
            margin: 8px auto 0;
            background: transparent;
            border: none;
            color: #6b7280;
            text-decoration: underline;
            cursor: pointer;
        }

        .topbar-logo {
            height: 110px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .toolbar {
            display: flex;
            align-items: center;
        }

        .office-user-toolbar {
            align-self: flex-start;
            margin-left: auto;
        }

        .office-user-menu {
            position: relative;
        }

        .office-user-menu summary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-height: 48px;
            padding: 6px 8px 6px 16px;
            border: 1px solid rgba(255, 255, 255, 0.48);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            cursor: pointer;
            list-style: none;
        }

        .office-user-menu summary::-webkit-details-marker {
            display: none;
        }

        .office-user-name {
            max-width: 220px;
            overflow: hidden;
            color: #fff;
            font-size: 0.92rem;
            font-weight: 800;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .office-user-avatar {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            overflow: hidden;
            border-radius: 999px;
            background: #f7d982;
            color: #173c66;
            font-weight: 950;
            border: 2px solid rgba(255, 255, 255, 0.82);
        }

        .office-user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .office-user-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 20;
            width: min(280px, calc(100vw - 40px));
            display: grid;
            gap: 12px;
            padding: 14px;
            border-radius: 18px;
            border: 1px solid #ded5c8;
            background: #fcfbf7;
            color: var(--navy-deep);
            box-shadow: 0 18px 44px rgba(15, 23, 42, 0.2);
        }

        .office-user-dropdown strong,
        .office-user-dropdown span {
            display: block;
            overflow-wrap: anywhere;
        }

        .office-user-dropdown span {
            margin-top: 3px;
            color: var(--muted);
            font-size: 0.84rem;
            font-weight: 700;
        }

        .office-user-dropdown form {
            margin: 0;
        }

        .office-user-dropdown .topbar-action {
            width: 100%;
            justify-content: center;
            background: var(--navy);
            color: #fff;
            border-color: var(--navy);
        }

        .topbar-logo {
            height: 65px;
            width: auto;
            object-fit: contain;
        }

        @media (max-width: 980px) {
            .office-archive-filter,
            .office-queue-filter {
                grid-template-columns: 1fr;
            }

            .office-section-header {
                flex-direction: column;
            }

            .record-meta {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .office-dashboard {
                padding: 22px 18px 32px;
            }

            .office-tabs {
                padding: 6px;
            }

            .office-tab {
                width: 100%;
            }

            .record-top {
                flex-direction: column;
            }

            .record-actions {
                width: 100%;
                align-items: flex-start;
            }

            .office-action-buttons {
                justify-content: flex-start;
            }

            .office-user-toolbar,
            .office-user-menu,
            .office-user-menu summary {
                width: 100%;
            }

            .office-user-menu summary {
                justify-content: space-between;
            }

            .office-modal-actions {
                flex-direction: column;
            }

            .office-modal-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const detailModal = document.getElementById('officeDetailModal');
            const detailTitle = document.getElementById('officeModalTitle');
            const detailMeta = document.getElementById('officeModalMeta');
            const detailStepStatus = document.getElementById('modalStepStatus');
            const detailClearanceStatus = document.getElementById('modalClearanceStatus');
            const detailDesignation = document.getElementById('modalDesignation');
            const detailLastProcessed = document.getElementById('modalLastProcessed');
            const detailNoteLabel = document.getElementById('modalNoteLabel');
            const detailPreviousNote = document.getElementById('modalPreviousNote');
            const detailButtons = document.querySelectorAll('.detail-trigger');
            const detailCloseButton = document.getElementById('closeOfficeModal');
            const detailCancelButton = document.getElementById('cancelOfficeModal');

            const approveModal = document.getElementById('approveModal');
            const approveForm = document.getElementById('approveForm');
            const approveMeta = document.getElementById('approveModalMeta');
            const approveButtons = document.querySelectorAll('.approve-trigger');
            const closeApproveModal = document.getElementById('closeApproveModal');
            const cancelApproveModal = document.getElementById('cancelApproveModal');

            const flagModal = document.getElementById('flagModal');
            const flagForm = document.getElementById('flagForm');
            const flagMeta = document.getElementById('flagModalMeta');
            const flagStepId = document.getElementById('flagStepId');
            const flagRemarks = document.getElementById('flagRemarks');
            const flagButtons = document.querySelectorAll('.flag-trigger');
            const closeFlagModal = document.getElementById('closeFlagModal');
            const cancelFlagModal = document.getElementById('cancelFlagModal');

            const undoModal = document.getElementById('undoModal');
            const undoForm = document.getElementById('undoForm');
            const undoMeta = document.getElementById('undoModalMeta');
            const undoButtons = document.querySelectorAll('.undo-trigger');
            const closeUndoModal = document.getElementById('closeUndoModal');
            const cancelUndoModal = document.getElementById('cancelUndoModal');

            const undoFlagModal = document.getElementById('undoFlagModal');
            const undoFlagForm = document.getElementById('undoFlagForm');
            const undoFlagMeta = document.getElementById('undoFlagModalMeta');
            const undoFlagButtons = document.querySelectorAll('.undo-flag-trigger');
            const closeUndoFlagModal = document.getElementById('closeUndoFlagModal');
            const cancelUndoFlagModal = document.getElementById('cancelUndoFlagModal');
            const profileModal = document.getElementById('officeStudentProfileModal');
            const profileBody = document.getElementById('officeStudentProfileBody');
            const profileLinks = document.querySelectorAll('[data-office-profile-link]');
            const closeProfileModal = document.getElementById('closeOfficeStudentProfile');
            const cancelProfileModal = document.getElementById('cancelOfficeStudentProfile');
            const workloadReportModal = document.getElementById('workloadReportModal');
            const openWorkloadReport = document.getElementById('openWorkloadReport');
            const closeWorkloadReport = document.getElementById('closeWorkloadReport');
            const cancelWorkloadReport = document.getElementById('cancelWorkloadReport');

            const reopenStepId = @json(old('step_id'));
            const oldRemarks = @json(old('remarks'));

            function showModal(modal) {
                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            }

            function hideModal(modal) {
                modal.hidden = true;
                document.body.style.overflow = '';
            }

            function openDetailModal(button) {
                detailTitle.textContent = button.dataset.modalStudentName;
                detailMeta.textContent = button.dataset.modalStudentMeta;
                detailStepStatus.textContent = button.dataset.modalStepStatus;
                detailClearanceStatus.textContent = 'Full Clearance Status: ' + button.dataset.modalClearanceStatus;
                detailDesignation.textContent = button.dataset.modalDesignation || '-';
                detailLastProcessed.textContent = button.dataset.modalLastProcessed;
                detailNoteLabel.textContent = button.dataset.modalNoteLabel || 'Previous Office Note';
                detailPreviousNote.textContent = button.dataset.modalPreviousNote;
                showModal(detailModal);
            }

            function openApproveModal(button) {
                approveMeta.textContent = button.dataset.studentName;
                approveForm.action = button.dataset.stepAction;
                showModal(approveModal);
            }

            function openFlagModal(button, preserveRemarks = false) {
                flagMeta.textContent = button.dataset.studentName;
                flagForm.action = button.dataset.stepAction;
                flagStepId.value = button.dataset.stepId;
                flagRemarks.value = preserveRemarks ? (oldRemarks || '') : '';
                showModal(flagModal);
            }

            function openUndoModal(button) {
                undoMeta.textContent = button.dataset.studentName;
                undoForm.action = button.dataset.stepAction;
                showModal(undoModal);
            }

            function openUndoFlagModal(button) {
                undoFlagMeta.textContent = button.dataset.studentName;
                undoFlagForm.action = button.dataset.stepAction;
                showModal(undoFlagModal);
            }

            async function openProfileModal(link) {
                if (!profileModal || !profileBody || link.getAttribute('aria-disabled') === 'true') {
                    return;
                }

                profileBody.innerHTML = '<div class="empty-state">Loading student profile...</div>';
                showModal(profileModal);

                try {
                    const response = await fetch(link.dataset.studentProfileUrl || link.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (!response.ok) {
                        throw new Error('Profile request failed');
                    }

                    profileBody.innerHTML = await response.text();
                } catch (error) {
                    profileBody.innerHTML = '<div class="empty-state">Unable to load the student profile. Please refresh and try again.</div>';
                }
            }

            detailButtons.forEach(button => {
                button.addEventListener('click', function () {
                    openDetailModal(button);
                });
            });

            approveButtons.forEach(button => {
                button.addEventListener('click', function () {
                    openApproveModal(button);
                });
            });

            flagButtons.forEach(button => {
                button.addEventListener('click', function () {
                    openFlagModal(button, false);
                });
            });

            undoButtons.forEach(button => {
                button.addEventListener('click', function () {
                    openUndoModal(button);
                });
            });

            undoFlagButtons.forEach(button => {
                button.addEventListener('click', function () {
                    openUndoFlagModal(button);
                });
            });

            profileLinks.forEach(link => {
                link.addEventListener('click', function (event) {
                    event.preventDefault();
                    openProfileModal(link);
                });
            });

            openWorkloadReport?.addEventListener('click', function () {
                showModal(workloadReportModal);
            });

            if (reopenStepId) {
                const matchingFlagButton = Array.from(flagButtons).find(button => button.dataset.stepId === String(reopenStepId));

                if (matchingFlagButton) {
                    openFlagModal(matchingFlagButton, true);
                }
            }

            detailCloseButton.addEventListener('click', function () {
                hideModal(detailModal);
            });

            detailCancelButton.addEventListener('click', function () {
                hideModal(detailModal);
            });

            closeApproveModal.addEventListener('click', function () {
                hideModal(approveModal);
            });

            cancelApproveModal.addEventListener('click', function () {
                hideModal(approveModal);
            });

            closeFlagModal.addEventListener('click', function () {
                hideModal(flagModal);
            });

            cancelFlagModal.addEventListener('click', function () {
                hideModal(flagModal);
            });

            closeUndoModal.addEventListener('click', function () {
                hideModal(undoModal);
            });

            cancelUndoModal.addEventListener('click', function () {
                hideModal(undoModal);
            });

            closeUndoFlagModal.addEventListener('click', function () {
                hideModal(undoFlagModal);
            });

            cancelUndoFlagModal.addEventListener('click', function () {
                hideModal(undoFlagModal);
            });

            closeProfileModal?.addEventListener('click', function () {
                hideModal(profileModal);
            });

            cancelProfileModal?.addEventListener('click', function () {
                hideModal(profileModal);
            });

            closeWorkloadReport?.addEventListener('click', function () {
                hideModal(workloadReportModal);
            });

            cancelWorkloadReport?.addEventListener('click', function () {
                hideModal(workloadReportModal);
            });

            [detailModal, approveModal, flagModal, undoModal, undoFlagModal, profileModal, workloadReportModal].forEach(modal => {
                if (!modal) {
                    return;
                }

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        hideModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    [detailModal, approveModal, flagModal, undoModal, undoFlagModal, profileModal, workloadReportModal].forEach(modal => {
                        if (!modal) {
                            return;
                        }

                        if (!modal.hidden) {
                            hideModal(modal);
                        }
                    });
                }
            });
        });
    </script>
@endsection
