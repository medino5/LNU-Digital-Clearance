@extends('layouts.portal', [
    'title' => 'Programs',
    'subtitle' => 'Manage the official program code, name, and organization label.',
])

@section('page')
    @php
        $activeFormKey = old('_form_key');
        $programCreateFormKey = 'program-create';
        $shouldOpenCreate = $activeFormKey === $programCreateFormKey;
    @endphp

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>Programs</h1>
                <p>Manage program codes, names, and organization labels.</p>
            </div>

            <button
                type="button"
                class="management-primary-action"
                data-modal-open="program-create-card"
            >
                Add Program
            </button>
        </section>

        <section class="program-overview-grid" aria-label="Program overview">
            <article class="program-metric-card">
                <span class="program-metric-icon">S</span>
                <div>
                    <p>Total Students</p>
                    <strong>{{ number_format($programStats['total_students']) }}</strong>
                    <span>Across {{ number_format($programStats['program_count']) }} official programs</span>
                </div>
            </article>

            <article class="program-metric-card">
                <span class="program-metric-icon">P</span>
                <div>
                    <p>Programs With Students</p>
                    <strong>{{ number_format($programStats['programs_with_students']) }}/{{ number_format($programStats['program_count']) }}</strong>
                    <span>{{ number_format($programStats['program_count'] - $programStats['programs_with_students']) }} still need enrolled records</span>
                </div>
            </article>

            <article class="program-metric-card">
                <span class="program-metric-icon">L</span>
                <div>
                    <p>Largest Roster</p>
                    <strong>{{ $programStats['largest_program_code'] }}</strong>
                    <span>{{ number_format($programStats['largest_program_students']) }} students</span>
                </div>
            </article>

            <article class="program-metric-card">
                <span class="program-metric-icon">A</span>
                <div>
                    <p>Average Roster</p>
                    <strong>{{ number_format($programStats['average_students']) }}</strong>
                    <span>Students per program</span>
                </div>
            </article>
        </section>

        <section class="admin-section-card management-card program-distribution-card">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Enrollment Distribution</div>
                    <h2>Program roster balance</h2>
                    <p class="management-card-kicker">Quickly spot which programs have complete rosters and which ones still need student records.</p>
                </div>
            </div>

            <div class="program-distribution-list">
                @foreach($programs as $program)
                    @php
                        $barWidth = $programStats['max_students'] > 0
                            ? max(2, min(100, round(($program->students_count / $programStats['max_students']) * 100)))
                            : 0;
                        $share = $programStats['total_students'] > 0
                            ? round(($program->students_count / $programStats['total_students']) * 100, 1)
                            : 0;
                        $statusClass = match (true) {
                            $program->students_count === 0 => 'danger',
                            $program->students_count < ($programStats['max_students'] * 0.25) => 'warning',
                            default => 'success',
                        };
                        $statusLabel = match ($statusClass) {
                            'danger' => 'No students',
                            'warning' => 'Low roster',
                            default => 'Active roster',
                        };
                    @endphp

                    <div class="program-distribution-row">
                        <div class="program-distribution-label">
                            <strong>{{ $program->code }}</strong>
                            <span>{{ number_format($program->students_count) }} students / {{ $share }}%</span>
                        </div>
                        <div class="program-distribution-track" aria-label="{{ $program->code }} student distribution">
                            <span class="program-distribution-fill {{ $statusClass }}" style="width: {{ $barWidth }}%"></span>
                        </div>
                        <span class="program-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="admin-section-card management-card" id="program-records">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Program Records</div>
                    <h2>Official programs</h2>
                    <p class="management-card-kicker">Program code, organization label, roster count, and available maintenance actions.</p>
                </div>
            </div>

            <div class="management-table-wrap">
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Program Name</th>
                            <th>Organization</th>
                            <th>Students</th>
                            <th>Status</th>
                            <th class="management-action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            @php
                                $programUpdateFormKey = 'program-update-' . $program->id;
                                $share = $programStats['total_students'] > 0
                                    ? round(($program->students_count / $programStats['total_students']) * 100, 1)
                                    : 0;
                                $rowStatusClass = match (true) {
                                    $program->students_count === 0 => 'danger',
                                    $program->students_count < ($programStats['max_students'] * 0.25) => 'warning',
                                    default => 'success',
                                };
                                $rowStatusLabel = match ($rowStatusClass) {
                                    'danger' => 'No students',
                                    'warning' => 'Low roster',
                                    default => 'Active',
                                };
                            @endphp

                            <tr>
                                <td>
                                    <strong class="program-code">{{ $program->code }}</strong>
                                </td>
                                <td>
                                    <div class="table-main-text">{{ $program->name }}</div>
                                </td>
                                <td>
                                    <span class="org-pill">{{ $program->org_name }}</span>
                                </td>
                                <td>
                                    <div class="program-table-count">
                                        <strong>{{ number_format($program->students_count) }}</strong>
                                        <span>{{ $share }}% of total</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="program-status-pill {{ $rowStatusClass }}">{{ $rowStatusLabel }}</span>
                                </td>
                                <td>
                                    <div class="table-action-stack">
                                        <button
                                            type="button"
                                            class="button secondary table-action-button"
                                            data-modal-open="program-edit-{{ $program->id }}"
                                        >
                                            Edit
                                        </button>

                                        @if($program->students_count === 0)
                                            <button
                                                type="button"
                                                class="button warn table-action-button"
                                                data-modal-open="program-delete-{{ $program->id }}"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">No programs added yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- CREATE MODAL --}}
        <div
            class="management-modal {{ $shouldOpenCreate ? 'is-open' : '' }}"
            id="program-create-card"
            data-modal
        >
            <div class="management-modal-panel">
                <div class="management-modal-header">
                    <div>
                        <div class="eyebrow">Create Program</div>
                        <h2>Add Program</h2>
                    </div>

                    <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.programs.store') }}">
                    @csrf
                    <input type="hidden" name="_form_key" value="{{ $programCreateFormKey }}">

                    <div class="field-grid">
                        <label>
                            Program Code
                            <input
                                type="text"
                                name="code"
                                placeholder="BSIT"
                                value="{{ $shouldOpenCreate ? old('code') : '' }}"
                                maxlength="5"
                                required
                            >
                            @if($shouldOpenCreate)
                                <x-field-error field="code" bag="programCreate" />
                            @endif
                        </label>

                        <label>
                            Organization Name
                            <input
                                type="text"
                                name="org_name"
                                placeholder="DIGITS"
                                value="{{ $shouldOpenCreate ? old('org_name') : '' }}"
                                maxlength="100"
                                required
                            >
                            @if($shouldOpenCreate)
                                <x-field-error field="org_name" bag="programCreate" />
                            @endif
                        </label>
                    </div>

                    <p class="mini">Letters, numbers, and hyphens only. Saved in uppercase.</p>

                    <label>
                        Program Name
                        <input
                            type="text"
                            name="name"
                            placeholder="Bachelor of Science in Information Technology"
                            value="{{ $shouldOpenCreate ? old('name') : '' }}"
                            maxlength="120"
                            required
                        >
                        @if($shouldOpenCreate)
                            <x-field-error field="name" bag="programCreate" />
                        @endif
                    </label>

                    <div class="form-actions modal-actions">
                        <button type="button" class="secondary" data-modal-close>Cancel</button>
                        <button
                            type="submit"
                            data-loading-button
                            data-loading-text="Saving Program..."
                        >
                            Save Program
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- EDIT MODALS --}}
        @foreach($programs as $program)
            @php
                $programUpdateFormKey = 'program-update-' . $program->id;
                $shouldOpenEdit = $activeFormKey === $programUpdateFormKey;
            @endphp

            <div
                class="management-modal {{ $shouldOpenEdit ? 'is-open' : '' }}"
                id="program-edit-{{ $program->id }}"
                data-modal
            >
                <div class="management-modal-panel">
                    <div class="management-modal-header">
                        <div>
                            <div class="eyebrow">Edit Program</div>
                            <h2>{{ $program->code }}</h2>
                        </div>

                        <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.programs.update', $program) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form_key" value="{{ $programUpdateFormKey }}">

                        <div class="field-grid">
                            <label>
                                Program Code
                                <input
                                    type="text"
                                    name="code"
                                    value="{{ $shouldOpenEdit ? old('code', $program->code) : $program->code }}"
                                    maxlength="15"
                                    required
                                >
                                @if($shouldOpenEdit)
                                    <x-field-error field="code" bag="programUpdate" />
                                @endif
                            </label>

                            <label>
                                Organization Name
                                <input
                                    type="text"
                                    name="org_name"
                                    value="{{ $shouldOpenEdit ? old('org_name', $program->org_name) : $program->org_name }}"
                                    maxlength="100"
                                    required
                                >
                                @if($shouldOpenEdit)
                                    <x-field-error field="org_name" bag="programUpdate" />
                                @endif
                            </label>
                        </div>

                        <p class="mini">Letters, numbers, and hyphens only. Saved in uppercase.</p>

                        <label>
                            Program Name
                            <input
                            type="text"
                            name="name"
                            value="{{ $shouldOpenEdit ? old('name', $program->name) : $program->name }}"
                            maxlength="100"
                            required
                        >
                            @if($shouldOpenEdit)
                                <x-field-error field="name" bag="programUpdate" />
                            @endif
                        </label>

                        <div class="form-actions modal-actions">
                            <button type="button" class="secondary" data-modal-close>Cancel</button>
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Updating Program..."
                            >
                                Update Program
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($program->students_count === 0)
                <div
                    class="management-modal"
                    id="program-delete-{{ $program->id }}"
                    data-modal
                >
                    <div class="management-modal-panel">
                        <div class="management-modal-header">
                            <div>
                                <div class="eyebrow">Delete Program</div>
                                <h2>{{ $program->code }}</h2>
                            </div>

                            <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                        </div>

                        <form method="POST" action="{{ route('admin.programs.destroy', $program) }}">
                            @csrf
                            @method('DELETE')

                            <p class="callout error">
                                This permanently deletes the program record. Type
                                <strong>DELETE {{ $program->code }}</strong> to confirm.
                            </p>

                            <label>
                                Confirmation
                                <input
                                    type="text"
                                    name="delete_confirmation"
                                    autocomplete="off"
                                    required
                                >
                                <x-field-error field="delete_confirmation" bag="programDelete" />
                            </label>

                            <div class="form-actions modal-actions">
                                <button type="button" class="secondary" data-modal-close>Cancel</button>
                                <button
                                    type="submit"
                                    class="warn"
                                    data-loading-text="Deleting Program..."
                                >
                                    Delete Program
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
        <style>
            .program-overview-grid {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 1rem;
            }

            .program-metric-card {
                display: flex;
                align-items: flex-start;
                gap: 0.9rem;
                min-height: 128px;
                padding: 1.1rem;
                border: 1px solid var(--border-subtle);
                border-radius: 1rem;
                background:
                    linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(247, 245, 239, 0.78)),
                    var(--bg-surface);
                box-shadow: var(--card-shadow);
            }

            .program-metric-icon {
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                flex: 0 0 auto;
                border-radius: 14px;
                background: rgba(22, 52, 92, 0.09);
                color: var(--brand-navy);
                font-size: 1.1rem;
                font-weight: 800;
            }

            .program-metric-card p,
            .program-metric-card strong,
            .program-metric-card span {
                display: block;
                margin: 0;
            }

            .program-metric-card p {
                color: var(--text-muted);
                font-size: 0.72rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .program-metric-card strong {
                margin-top: 0.25rem;
                color: var(--text-primary);
                font-size: 1.6rem;
                line-height: 1.05;
                letter-spacing: -0.04em;
            }

            .program-metric-card span:not(.program-metric-icon) {
                margin-top: 0.35rem;
                color: var(--text-muted);
                font-size: 0.84rem;
                line-height: 1.35;
            }

            .program-distribution-card {
                background:
                    radial-gradient(circle at top left, rgba(212, 165, 58, 0.12), transparent 34%),
                    var(--bg-surface);
            }

            .program-distribution-list {
                display: grid;
                gap: 0.9rem;
            }

            .program-distribution-row {
                display: grid;
                grid-template-columns: minmax(150px, 190px) minmax(160px, 1fr) auto;
                align-items: center;
                gap: 1rem;
                padding: 0.9rem 1rem;
                border: 1px solid rgba(231, 227, 216, 0.88);
                border-radius: 0.9rem;
                background: rgba(255, 255, 255, 0.72);
            }

            .program-distribution-label strong,
            .program-distribution-label span {
                display: block;
            }

            .program-distribution-label strong {
                color: var(--text-primary);
                font-size: 0.95rem;
            }

            .program-distribution-label span {
                margin-top: 0.15rem;
                color: var(--text-muted);
                font-size: 0.78rem;
                white-space: nowrap;
            }

            .program-distribution-track {
                position: relative;
                height: 0.72rem;
                overflow: hidden;
                border-radius: 999px;
                background: rgba(15, 23, 42, 0.08);
            }

            .program-distribution-fill {
                position: absolute;
                inset: 0 auto 0 0;
                border-radius: inherit;
                background: var(--brand-navy);
            }

            .program-distribution-fill.success {
                background: linear-gradient(90deg, var(--status-info-text), var(--status-success-text));
            }

            .program-distribution-fill.warning {
                background: linear-gradient(90deg, var(--brand-gold), var(--status-warning-text));
            }

            .program-distribution-fill.danger {
                background: linear-gradient(90deg, var(--status-danger-bg), var(--status-danger-text));
            }

            .program-status-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 98px;
                padding: 0.35rem 0.65rem;
                border-radius: 999px;
                font-size: 0.72rem;
                font-weight: 800;
                white-space: nowrap;
            }

            .program-status-pill.success {
                background: var(--status-success-bg);
                color: var(--status-success-text);
            }

            .program-status-pill.warning {
                background: var(--status-warning-bg);
                color: var(--status-warning-text);
            }

            .program-status-pill.danger {
                background: var(--status-danger-bg);
                color: var(--status-danger-text);
            }

            .program-table-count {
                display: grid;
                gap: 0.15rem;
            }

            .program-table-count strong {
                color: var(--text-primary);
                font-size: 0.95rem;
            }

            .program-table-count span {
                color: var(--text-muted);
                font-size: 0.76rem;
                white-space: nowrap;
            }

            @media (max-width: 1180px) {
                .program-overview-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (max-width: 760px) {
                .program-overview-grid,
                .program-distribution-row {
                    grid-template-columns: 1fr;
                }

                .program-distribution-row {
                    align-items: stretch;
                }

                .program-status-pill {
                    width: fit-content;
                }
            }
        </style>
    @endpush
@endsection
