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
                            <p class="muted">Only students routed to your current designation appear here.</p>
                        </div>
                    </div>

                    <div class="list office-list">
                        @forelse($pendingSteps as $step)
                            @php
                                $student = $step->clearance->student;
                                $studentName = $student?->displayName() ?: 'Student record unavailable';
                                $studentId = $student?->student_id_number ?: 'No ID';
                                $programCode = $student?->program?->code ?: 'No program';
                                $yearLevel = $student?->yearLevelLabel() ?: 'No year level';
                                $studentMeta = $studentId . ' | ' . $programCode . ' | ' . $yearLevel;
                                $studentPhoto = $student?->user?->profilePhotoUrl();
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
                                            <button
                                                type="button"
                                                class="button ghost detail-trigger"
                                                data-modal-step-id="{{ $step->id }}"
                                                data-modal-student-name="{{ $studentName }}"
                                                data-modal-student-meta="{{ $studentMeta }}"
                                                data-modal-step-status="Awaiting Action"
                                                data-modal-clearance-status="{{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}"
                                                data-modal-last-processed="{{ optional($step->signed_at)->format('M d, Y h:i A') ?? '-' }}"
                                                data-modal-designation="{{ $step->office_label ?: '-' }}"
                                                data-modal-note-label="Previous Office Note"
                                                data-modal-previous-note="{{ $step->remarks ?: '-' }}"
                                            >
                                                View
                                            </button>

                                            <a
                                                href="{{ $student ? route('office.students.show', $student) : '#' }}"
                                                class="button profile-link"
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
                                    <p class="mini">
                                        <strong>Designation:</strong>
                                        {{ $step->office_label ?: '-' }}
                                    </p>

                                    <p class="mini">
                                        <strong>Clearance status:</strong>
                                        {{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}
                                    </p>

                                    <p class="mini">
                                        <strong>Last note:</strong>
                                        {{ $step->remarks ?: '-' }}
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
                        <div class="pagination-wrapper">
                            {{ $pendingSteps->links('pagination::bootstrap-5') }}
                        </div>
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

                    <div class="list office-list">
                        @forelse($archiveSteps as $step)
                            @php
                                $clearance = $step->clearance;
                                $student = $clearance?->student;
                                $studentName = $student?->displayName() ?: 'Student record unavailable';
                                $studentId = $student?->student_id_number ?: 'No ID';
                                $programCode = $student?->program?->code ?: 'No program';
                                $yearLevel = $student?->yearLevelLabel() ?: 'No year level';
                                $studentMeta = $studentId . ' | ' . $programCode . ' | ' . $yearLevel;
                                $studentPhoto = $student?->user?->profilePhotoUrl();
                                $clearanceStatus = $clearance?->status
                                    ? ucwords(str_replace('_', ' ', $clearance->status))
                                    : 'Unavailable';
                            @endphp

                            <div class="record office-record processed-record">
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
                                        <span class="badge {{ $step->status }}">
                                            {{ $step->status === 'flagged' ? 'Rejected' : 'Approved' }}
                                        </span>

                                        <div class="office-action-buttons">
                                            <button
                                                type="button"
                                                class="button ghost detail-trigger"
                                                data-modal-step-id="{{ $step->id }}"
                                                data-modal-student-name="{{ $studentName }}"
                                                data-modal-student-meta="{{ $studentMeta }}"
                                                data-modal-step-status="{{ $step->status === 'flagged' ? 'Rejected' : 'Approved' }}"
                                                data-modal-clearance-status="{{ $clearanceStatus }}"
                                                data-modal-last-processed="{{ optional($step->signed_at)->format('M d, Y h:i A') ?? '-' }}"
                                                data-modal-designation="{{ $step->office_label ?: '-' }}"
                                                data-modal-note-label="{{ $step->status === 'flagged' ? 'Reject Reason' : 'Processed Note' }}"
                                                data-modal-previous-note="{{ $step->remarks ?: '-' }}"
                                            >
                                                View
                                            </button>

                                            <a
                                                href="{{ $student ? route('office.students.show', $student) : '#' }}"
                                                class="button profile-link"
                                                aria-disabled="{{ $student ? 'false' : 'true' }}"
                                            >
                                                View Profile
                                            </a>

                                            @if($step->status === 'approved')
                                                <button
                                                    type="button"
                                                    class="button ghost undo-trigger"
                                                    data-step-id="{{ $step->id }}"
                                                    data-student-name="{{ $studentName }}"
                                                    data-step-action="{{ route('office.steps.process', $step) }}"
                                                >
                                                    Undo Approval
                                                </button>
                                            @elseif($step->status === 'flagged')
                                                <button
                                                    type="button"
                                                    class="button ghost undo-flag-trigger"
                                                    data-step-id="{{ $step->id }}"
                                                    data-student-name="{{ $studentName }}"
                                                    data-step-action="{{ route('office.steps.process', $step) }}"
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
                                        {{ $step->office_label ?: '-' }}
                                    </p>

                                    <p class="mini">
                                        <strong>Processed:</strong>
                                        {{ optional($step->signed_at)->format('M d, Y h:i A') ?? 'Pending timestamp' }}
                                    </p>

                                    <p class="mini">
                                        <strong>{{ $step->status === 'flagged' ? 'Reject Reason' : 'Remarks' }}:</strong>
                                        {{ $step->remarks ?: '-' }}
                                    </p>

                                    <p class="mini">
                                        <strong>Student clearance status:</strong>
                                        {{ $clearanceStatus }}
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
                        <div class="pagination-wrapper">
                            {{ $archiveSteps->links('pagination::bootstrap-5') }}
                        </div>
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

    <style>
        .office-modal-close {
            display: none !important;
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

        .office-dashboard {
            gap: 24px;
            width: 100%;
            margin: 0;
            padding: 28px 32px 40px;
            background: transparent;
            border-radius: 0;
        }

        .office-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            padding: 8px;
            border: 1px solid #ded5c8;
            border-radius: 22px;
            background: #f2ece2;
        }

        .office-tab {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 18px;
            border-radius: 16px;
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
            background: white;
            box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
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

        .office-archive-filter {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(150px, 180px) minmax(170px, 210px) auto auto;
            gap: 12px;
            align-items: end;
            margin: 0 0 18px;
            padding: 16px;
            border: 1px solid #ded5c8;
            border-radius: 20px;
            background: #f8f7f3;
        }

        .office-archive-filter label {
            display: grid;
            gap: 6px;
            font-weight: 700;
            color: var(--navy-deep);
        }

        .office-archive-filter span {
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .office-archive-filter input,
        .office-archive-filter select {
            width: 100%;
            min-height: 42px;
            border: 1px solid #d6ccbd;
            border-radius: 12px;
            padding: 10px 12px;
            background: white;
        }

        .office-record {
            border-radius: 18px;
            padding: 18px;
            border: 1px solid #ddd3c4;
            background: #f8f7f3;
            box-shadow: none;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        .office-record:hover {
            border-color: #cdbb9c;
            background: #fcfbf7;
            transform: none;
            box-shadow: none;
        }

        .record-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .record-name {
            display: block;
            font-size: 1.08rem;
            margin-bottom: 4px;
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
            width: 48px;
            height: 48px;
            flex: 0 0 48px;
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
            gap: 10px;
            flex-shrink: 0;
        }

        .office-action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .record-meta {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid #d9d1c4;
            display: grid;
            gap: 8px;
        }

        .record-meta p {
            margin: 0;
            overflow-wrap: anywhere;
        }

        .button.ghost,
        .detail-trigger,
        .profile-link {
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
        .profile-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(16, 24, 40, 0.08);
            border-color: rgba(22, 56, 95, 0.35);
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
            .office-archive-filter {
                grid-template-columns: 1fr;
            }

            .office-section-header {
                flex-direction: column;
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

            [detailModal, approveModal, flagModal, undoModal, undoFlagModal].forEach(modal => {
                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        hideModal(modal);
                    }
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    [detailModal, approveModal, flagModal, undoModal, undoFlagModal].forEach(modal => {
                        if (!modal.hidden) {
                            hideModal(modal);
                        }
                    });
                }
            });
        });
    </script>
@endsection
