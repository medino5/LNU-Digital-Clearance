@extends('layouts.portal', ['title' => 'Office Dashboard'])

@section('page')
    <div class="topbar">
        <div>
            <h1>{{ $officeAccount->display_name }}</h1>
        </div>
        <div class="toolbar">
            <a class="button topbar-action" href="{{ route('admin.login') }}">Switch to Admin Portal</a>
            <form method="POST" action="{{ route('portal.logout') }}" class="topbar-form">
                @csrf
                <button type="submit" class="topbar-action">Log Out / Switch Account</button>
            </form>
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

        @if($errors->any())
            <div class="callout error">{{ $errors->first() }}</div>
        @endif

        <div class="office-columns">
            <section class="card office-column">
                <div class="eyebrow">Pending</div>
                <h2>Awaiting your action</h2>

                <div class="list">
                    @forelse($pendingSteps as $step)
                        @php($student = $step->clearance->student)

                        <div class="record office-record pending-record">
                            <div class="record-top">
                                <div>
                                    <strong class="record-name">{{ $student->user->name }}</strong>
                                    <div class="mini">
                                        {{ $student->student_id_number }} | {{ $student->program->code }} | {{ $student->yearLevelLabel() }}
                                    </div>
                                </div>

                                <div class="record-actions">
                                    <span class="badge awaiting_action">Awaiting Action</span>
                                    <button
                                        type="button"
                                        class="button ghost detail-trigger"
                                        data-modal-student-name="{{ $student->user->name }}"
                                        data-modal-student-meta="{{ $student->student_id_number }} | {{ $student->program->code }} | {{ $student->yearLevelLabel() }}"
                                        data-modal-step-status="Awaiting Action"
                                        data-modal-clearance-status="{{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}"
                                        data-modal-last-processed="{{ optional($step->signed_at)->format('M d, Y h:i A') ?? '—' }}"
                                        data-modal-previous-note="{{ $step->remarks ?: '—' }}"
                                        data-modal-action="{{ route('office.steps.process', $step) }}"
                                    >
                                        View Details
                                    </button>
                                </div>
                            </div>

                            <div class="record-meta">
                                <p class="mini">
                                    <strong>Clearance status:</strong>
                                    {{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}
                                </p>

                                <p class="mini">
                                    <strong>Last note:</strong>
                                    {{ $step->remarks ?: '—' }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="record office-empty-state">
                            <p class="muted" style="margin: 0;">No routed students are waiting on this office right now.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="card office-column">
                <div class="eyebrow">Processed</div>
                <h2>Recently completed office actions</h2>

                <div class="list">
                    @forelse($processedSteps as $step)
                        @php($student = $step->clearance->student)

                        <div class="record office-record processed-record">
                            <div class="record-top">
                                <div>
                                    <strong class="record-name">{{ $student->user->name }}</strong>
                                    <div class="mini">
                                        {{ $student->student_id_number }} | {{ $student->program->code }} | {{ $student->yearLevelLabel() }}
                                    </div>
                                </div>

                                <div class="record-actions">
                                    <span class="badge {{ $step->status }}">
                                        {{ ucwords(str_replace('_', ' ', $step->status)) }}
                                    </span>
                                </div>
                            </div>

                            <div class="record-meta">
                                <p class="mini">
                                    <strong>Processed:</strong>
                                    {{ optional($step->signed_at)->format('M d, Y h:i A') ?? 'Pending timestamp' }}
                                </p>

                                <p class="mini">
                                    <strong>Remarks:</strong>
                                    {{ $step->remarks ?: '—' }}
                                </p>

                                <p class="mini">
                                    <strong>Student clearance status:</strong>
                                    {{ ucwords(str_replace('_', ' ', $step->clearance->status)) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="record office-empty-state">
                            <p class="muted" style="margin: 0;">No processed records yet for this office account.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <div class="office-modal-backdrop" id="officeDetailModal" hidden>
        <div class="office-modal" role="dialog" aria-modal="true" aria-labelledby="officeModalTitle">
            <div class="office-modal-header">
                <div>
                    <h2 id="officeModalTitle">Student Name</h2>
                    <p id="officeModalMeta">Student meta</p>
                </div>

                <button type="button" class="office-modal-close" id="closeOfficeModal" aria-label="Close modal">
                    ×
                </button>
            </div>

            <div class="office-modal-body">
                <div class="office-status-row">
                    <span class="badge awaiting_action" id="modalStepStatus">Awaiting Action</span>
                    <span class="badge neutral" id="modalClearanceStatus">In Progress</span>
                </div>

                <div class="office-detail-block">
                    <p><strong>Last Processed:</strong> <span id="modalLastProcessed">—</span></p>
                </div>

                <div class="office-detail-block">
                    <label class="office-label">Previous Office Note</label>
                    <div class="office-note-box" id="modalPreviousNote">—</div>
                </div>

                <form method="POST" id="officeDetailForm">
                    @csrf

                    <label class="office-label" for="modalRemarks">Remarks / Flag Reason</label>
                    <p class="mini" style="margin: -8px 0 0;">Required when flagging. Optional when approving.</p>

                    <textarea
                        id="modalRemarks"
                        name="remarks"
                        rows="5"
                        placeholder="Add notes for the student or office history"
                    ></textarea>

                    <div class="office-modal-actions">
                        <button type="submit" name="action" value="approve">Approve</button>
                        <button type="submit" name="action" value="flag" class="warn">Flag</button>
                    </div>

                    <button type="button" class="office-cancel-link" id="cancelOfficeModal">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .office-dashboard {
            gap: 24px;
        }

        .office-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }

        .office-column {
            min-height: 100%;
        }

        .office-record {
            border-radius: 18px;
            padding: 20px;
            border: 1px solid #d8d3ca;
            background: #fffdfa;
            box-shadow: 0 1px 2px rgba(16, 24, 40, 0.04);
        }

        .record-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .record-name {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 4px;
        }

        .record-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
            flex-shrink: 0;
        }

        .record-meta {
            margin-top: 14px;
            display: grid;
            gap: 8px;
        }

        .record-meta p {
            margin: 0;
        }

        .button.ghost,
        .detail-trigger {
            background: transparent;
            border: 1px solid #9aa9c0;
            color: #294c7a;
            border-radius: 999px;
            padding: 10px 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .office-empty-state {
            padding: 24px 20px;
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
            justify-content: space-between;
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

        #officeDetailForm textarea {
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

        @media (max-width: 980px) {
            .office-columns {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .record-top {
                flex-direction: column;
            }

            .record-actions {
                width: 100%;
                align-items: flex-start;
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
            const modal = document.getElementById('officeDetailModal');
            const modalTitle = document.getElementById('officeModalTitle');
            const modalMeta = document.getElementById('officeModalMeta');
            const modalStepStatus = document.getElementById('modalStepStatus');
            const modalClearanceStatus = document.getElementById('modalClearanceStatus');
            const modalLastProcessed = document.getElementById('modalLastProcessed');
            const modalPreviousNote = document.getElementById('modalPreviousNote');
            const modalForm = document.getElementById('officeDetailForm');
            const modalRemarks = document.getElementById('modalRemarks');
            const openButtons = document.querySelectorAll('.detail-trigger');
            const closeButton = document.getElementById('closeOfficeModal');
            const cancelButton = document.getElementById('cancelOfficeModal');

            function openModal(button) {
                modalTitle.textContent = button.dataset.modalStudentName;
                modalMeta.textContent = button.dataset.modalStudentMeta;
                modalStepStatus.textContent = button.dataset.modalStepStatus;
                modalClearanceStatus.textContent = 'Full Clearance Status: ' + button.dataset.modalClearanceStatus;
                modalLastProcessed.textContent = button.dataset.modalLastProcessed;
                modalPreviousNote.textContent = button.dataset.modalPreviousNote;
                modalForm.action = button.dataset.modalAction;
                modalRemarks.value = '';

                modal.hidden = false;
                document.body.style.overflow = 'hidden';
            }

            function closeModal() {
                modal.hidden = true;
                document.body.style.overflow = '';
            }

            openButtons.forEach(button => {
                button.addEventListener('click', function () {
                    openModal(button);
                });
            });

            closeButton.addEventListener('click', closeModal);
            cancelButton.addEventListener('click', closeModal);

            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });
        });
    </script>
@endsection