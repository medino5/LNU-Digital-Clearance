@extends('layouts.portal', [
    'title' => 'Download Reports',
    'subtitle' => 'Download completed clearance reports by semester and academic year.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))
    @php($selectedSemester = $semesters->firstWhere('id', $selectedSemesterId))
    @php($exportSemesterId = old('semester_id', $selectedSemesterId ?: ''))
    @php($exportAcademicYear = old('academic_year', $selectedAcademicYear ?: ($selectedSemester?->displayAcademicYear() ?? '')))
    @php($exportProgramCode = old('program_code', $selectedProgramCode ?? ''))

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>Download Reports</h1>
            </div>
        </section>

        <section class="admin-section-card management-card report-export-card" id="download-reports-panel">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Excel Export</div>
                    <h2>Completed clearances</h2>
                </div>
            </div>

            @if($activeFormKey === 'history-export')
                <div class="empty-state report-validation-state">
                    <x-field-error field="semester_id" bag="historyExport" />
                    <x-field-error field="academic_year" bag="historyExport" />
                    <x-field-error field="program_code" bag="historyExport" />
                </div>
            @endif

            <form method="POST" action="{{ route('admin.clearance-reports.completed.export') }}" id="history-export-form" class="history-export-form management-filter-grid">
                @csrf
                <input type="hidden" name="_form_key" value="history-export">
                <input type="hidden" name="academic_year" value="{{ $exportAcademicYear }}" data-export-academic-year-input>

                <label>
                    Report Period
                    <select name="semester_id" data-export-semester-select required>
                        <option value="">Choose semester and school year</option>
                        @foreach($semesters as $semester)
                            <option
                                value="{{ $semester->id }}"
                                data-academic-year="{{ $semester->displayAcademicYear() }}"
                                {{ (string) $exportSemesterId === (string) $semester->id ? 'selected' : '' }}
                            >
                                {{ $semester->label }} / {{ $semester->displayAcademicYear() }}
                            </option>
                        @endforeach
                    </select>
                    @if($activeFormKey === 'history-export')
                        <x-field-error field="semester_id" bag="historyExport" />
                    @endif
                </label>

                <label>
                    Program
                    <select name="program_code">
                        <option value="">All programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->code }}" {{ $exportProgramCode === $program->code ? 'selected' : '' }}>
                                {{ $program->code }} - {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                    @if($activeFormKey === 'history-export')
                        <x-field-error field="program_code" bag="historyExport" />
                    @endif
                </label>

                <button
                    type="submit"
                    class="button history-download-button"
                    data-loading-button
                    data-loading-text="Preparing Excel..."
                    {{ $semesters->isEmpty() ? 'disabled' : '' }}
                >
                    Download Excel Report
                </button>
            </form>

            <div class="report-preview-panel" data-report-preview>
                <div class="report-preview-heading">
                    <div>
                        <div class="eyebrow">Preview</div>
                        <h3 data-preview-title>Choose a report period</h3>
                    </div>
                    <span class="report-preview-chip" data-preview-scope>Waiting for filters</span>
                </div>

                <div class="report-preview-stats">
                    <div>
                        <span>Completed Clearances</span>
                        <strong data-preview-total>0</strong>
                    </div>
                    <div>
                        <span>Programs Included</span>
                        <strong data-preview-programs>0</strong>
                    </div>
                    <div>
                        <span>Latest Completion</span>
                        <strong data-preview-latest>-</strong>
                    </div>
                </div>

                <div class="report-preview-breakdown">
                    <div class="report-preview-list" data-preview-program-list>
                        <p class="muted">Select a semester and academic year to preview report coverage.</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
    <style>
        .history-export-form {
            grid-template-columns: minmax(260px, 1.25fr) minmax(240px, 1fr) auto;
        }

        .history-download-button {
            align-self: end;
            min-width: 220px;
        }

        .report-validation-state p {
            margin: 6px 0 0;
        }

        .report-preview-panel {
            display: grid;
            gap: 1rem;
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid var(--border-subtle);
            background:
                radial-gradient(circle at top right, rgba(212, 165, 58, 0.16), transparent 34%),
                rgba(255, 255, 255, 0.74);
        }

        .report-preview-heading {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .report-preview-heading h3 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.1rem;
            line-height: 1.2;
        }

        .report-preview-chip {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            padding: 0 0.75rem;
            border-radius: 999px;
            background: var(--status-info-bg);
            color: var(--status-info-text);
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .report-preview-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .report-preview-stats div {
            display: grid;
            gap: 0.2rem;
            padding: 0.85rem;
            border-radius: 0.85rem;
            background: var(--bg-surface);
            border: 1px solid rgba(23, 60, 102, 0.1);
            min-width: 0;
        }

        .report-preview-stats span {
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .report-preview-stats strong {
            color: var(--brand-navy);
            font-size: clamp(1.2rem, 2.4vw, 1.75rem);
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .report-preview-list {
            display: grid;
            gap: 0.55rem;
        }

        .report-preview-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.75rem 0.85rem;
            border-radius: 0.85rem;
            background: var(--bg-surface);
            border: 1px solid rgba(23, 60, 102, 0.08);
        }

        .report-preview-row strong {
            color: var(--text-primary);
            line-height: 1.3;
            overflow-wrap: anywhere;
        }

        .report-preview-row span {
            color: var(--text-muted);
            font-size: 0.82rem;
        }

        .report-preview-row b {
            color: var(--brand-navy);
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 980px) {
            .history-export-form {
                grid-template-columns: 1fr;
            }

            .history-download-button {
                width: 100%;
            }

            .report-preview-heading,
            .report-preview-stats {
                grid-template-columns: 1fr;
            }

            .report-preview-heading {
                display: grid;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const semesterSelect = document.querySelector('[data-export-semester-select]');
            const academicYearInput = document.querySelector('[data-export-academic-year-input]');
            const programSelect = document.querySelector('[name="program_code"]');
            const previewData = @json($reportPreview);
            const previewTitle = document.querySelector('[data-preview-title]');
            const previewScope = document.querySelector('[data-preview-scope]');
            const previewTotal = document.querySelector('[data-preview-total]');
            const previewPrograms = document.querySelector('[data-preview-programs]');
            const previewLatest = document.querySelector('[data-preview-latest]');
            const previewProgramList = document.querySelector('[data-preview-program-list]');

            if (!semesterSelect || !academicYearInput) {
                return;
            }

            const formatNumber = function (value) {
                return Number(value || 0).toLocaleString();
            };

            const formatDate = function (value) {
                if (!value) {
                    return '-';
                }

                const parsed = new Date(value);

                if (Number.isNaN(parsed.getTime())) {
                    return '-';
                }

                return parsed.toLocaleDateString(undefined, {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                });
            };

            const renderPreview = function () {
                if (!previewTitle || !previewProgramList) {
                    return;
                }

                const semesterId = semesterSelect.value;
                const academicYear = academicYearInput.value;
                const programCode = programSelect?.value || '';
                const semester = previewData.semesters?.[semesterId];

                if (!semesterId || !academicYear || !semester || semester.academicYear !== academicYear) {
                    previewTitle.textContent = 'Choose a valid report period';
                    previewScope.textContent = 'No preview yet';
                    previewTotal.textContent = '0';
                    previewPrograms.textContent = '0';
                    previewLatest.textContent = '-';
                    previewProgramList.innerHTML = '<p class="muted">Select a matching semester and academic year to preview report coverage.</p>';
                    return;
                }

                const rows = (previewData.rows || []).filter(function (row) {
                    return row.semesterId === semesterId && (!programCode || row.programCode === programCode);
                });

                const total = rows.reduce((sum, row) => sum + Number(row.total || 0), 0);
                const latest = rows
                    .map((row) => row.latestCompletedAt)
                    .filter(Boolean)
                    .sort()
                    .pop();

                previewTitle.textContent = `${semester.label} / ${academicYear}`;
                previewScope.textContent = programCode ? `${programCode} only` : 'All programs';
                previewTotal.textContent = formatNumber(total);
                previewPrograms.textContent = formatNumber(rows.length);
                previewLatest.textContent = formatDate(latest);

                if (rows.length === 0) {
                    previewProgramList.innerHTML = '<p class="muted">No completed clearances match these filters yet.</p>';
                    return;
                }

                const sortedRows = [...rows].sort((a, b) => Number(b.total || 0) - Number(a.total || 0));
                previewProgramList.replaceChildren(...sortedRows.slice(0, 7).map(function (row) {
                    const program = previewData.programs?.[row.programCode] || { code: row.programCode, name: row.programCode };
                    const item = document.createElement('div');
                    item.className = 'report-preview-row';
                    item.innerHTML = `
                        <div>
                            <strong>${program.code} - ${program.name}</strong>
                            <span>Latest completed: ${formatDate(row.latestCompletedAt)}</span>
                        </div>
                        <b>${formatNumber(row.total)}</b>
                    `;
                    return item;
                }));
            };

            const updateReportSummary = function () {
                const selectedOption = semesterSelect.options[semesterSelect.selectedIndex];
                const academicYear = selectedOption?.dataset.academicYear || '';

                academicYearInput.value = selectedOption?.value ? academicYear : '';

                renderPreview();
            };

            semesterSelect.addEventListener('change', updateReportSummary);
            programSelect?.addEventListener('change', renderPreview);
            updateReportSummary();
        });
    </script>
    @endpush
@endsection
