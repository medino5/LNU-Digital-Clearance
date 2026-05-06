@extends('layouts.portal', [
    'title' => 'Clearance History',
    'subtitle' => 'Filter completed clearances and export the Excel report.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))
    @php($selectedSemester = $semesters->firstWhere('id', $selectedSemesterId))
    @php($exportSemesterId = old('semester_id', $selectedSemesterId ?: ''))
    @php($exportAcademicYear = old('academic_year', $selectedAcademicYear ?: ($selectedSemester?->displayAcademicYear() ?? '')))
    @php($exportSemester = $semesters->firstWhere('id', (int) $exportSemesterId))
    @php($selectedHistoryLabel = $selectedSemester?->label ?? 'All semesters')
    @php($selectedHistoryAcademicYear = $selectedAcademicYear !== '' ? $selectedAcademicYear : 'All academic years')

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>Clearance History</h1>
                <p>Completed clearance records by semester and academic year. Narrow the table by period and download the official Excel workbook.</p>
            </div>

            <a href="#history-export-form" class="management-primary-action">
                Download Excel Report
            </a>
        </section>

        <section class="admin-section-card management-card history-filter-card" id="clearance-history-panel">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Completed Records</div>
                    <p class="management-card-kicker">Use these filters to control what appears in the history table.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.clearance-history.index') }}" class="history-filter-form management-filter-grid">
                <label>
                    Academic Year
                    <select name="history_academic_year">
                        <option value="">All academic years</option>
                        @foreach($academicYears as $academicYear)
                            <option value="{{ $academicYear }}" {{ $selectedAcademicYear === $academicYear ? 'selected' : '' }}>
                                {{ $academicYear }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Semester
                    <select name="history_semester">
                        <option value="">All semesters</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" {{ $selectedSemesterId === $semester->id ? 'selected' : '' }}>
                                {{ $semester->label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="button">Apply Filters</button>
                <a href="{{ route('admin.clearance-history.index') }}" class="button secondary management-secondary-action">Reset</a>
            </form>

            <div class="management-summary-strip">
                <span class="management-summary-pill">Table period</span>
                <strong>{{ $selectedHistoryLabel }}</strong>
                <span class="mini">{{ $selectedHistoryAcademicYear }}</span>
            </div>

            @if($historyHasRecords)
                @foreach($history as $semesterLabel => $records)
                    <article class="history-group-card">
                        <div class="history-group-header">
                            <div>
                                <div class="eyebrow">Semester Group</div>
                                <h2>{{ $semesterLabel }}</h2>
                            </div>
                            <span class="management-summary-pill">
                                {{ $records->count() }} completed
                            </span>
                        </div>

                        <div class="management-table-wrap">
                            <table class="management-table history-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Program</th>
                                        <th>Reference</th>
                                        <th>Completed</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($records as $clearance)
                                        <tr>
                                            <td>
                                                <div class="table-main-text">{{ $clearance->student_name }}</div>
                                                <span class="mini">{{ $clearance->student_id_number }} - Year {{ $clearance->year_level }}</span>
                                            </td>
                                            <td>
                                                <strong class="program-code">{{ $clearance->program_code }}</strong>
                                                <div class="mini history-program-name">{{ $clearance->program_name }}</div>
                                            </td>
                                            <td>
                                                <span class="history-reference">{{ $clearance->reference_number }}</span>
                                            </td>
                                            <td>
                                                {{ optional($clearance->completed_at)->format('M d, Y h:i A') }}
                                            </td>
                                            <td>
                                                <details class="history-step-details">
                                                    <summary>View offices</summary>
                                                    <div class="history-step-list">
                                                        @foreach($clearance->steps as $step)
                                                            <div>
                                                                <strong>{{ $step->office_label }}</strong>
                                                                @if($step->signed_at)
                                                                    <span>{{ $step->signed_at->format('M d, Y h:i A') }}</span>
                                                                @else
                                                                    <span>No signing time recorded</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </details>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </article>
                @endforeach

                <div class="pagination-wrapper">
                    {{ $historyPaginator->links('pagination::bootstrap-5') }}
                </div>
            @else
                <div class="empty-state history-empty-state">
                    <strong>No completed clearances found.</strong>
                    <p>Try a different semester or academic year before downloading a report.</p>
                </div>
            @endif
        </section>

        <section class="admin-section-card management-card report-export-card">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Excel Export</div>
                    <h2>Download completed clearance workbook</h2>
                    <p class="management-card-kicker">Choose the exact semester and academic year for the report file.</p>
                </div>
            </div>

            <div class="management-summary-strip" data-report-summary>
                <span class="management-summary-pill">Selected Report Period</span>
                <strong data-report-semester-text>{{ $exportSemester?->label ?? 'Choose semester' }}</strong>
                <span class="mini" data-report-academic-year-text>{{ $exportAcademicYear ?: 'Choose academic year' }}</span>
            </div>

            @if($activeFormKey === 'history-export')
                <div class="empty-state report-validation-state">
                    <p class="mini">Report download needs a semester and academic year.</p>
                    <x-field-error field="semester_id" bag="historyExport" />
                    <x-field-error field="academic_year" bag="historyExport" />
                </div>
            @endif

            <form method="POST" action="{{ route('admin.clearance-reports.completed.export') }}" id="history-export-form" class="history-export-form management-filter-grid">
                @csrf
                <input type="hidden" name="_form_key" value="history-export">

                <label>
                    Report Semester
                    <select name="semester_id" data-export-semester-select required>
                        <option value="">Choose semester</option>
                        @foreach($semesters as $semester)
                            <option
                                value="{{ $semester->id }}"
                                data-academic-year="{{ $semester->displayAcademicYear() }}"
                                {{ (string) $exportSemesterId === (string) $semester->id ? 'selected' : '' }}
                            >
                                {{ $semester->label }}
                            </option>
                        @endforeach
                    </select>
                    @if($activeFormKey === 'history-export')
                        <x-field-error field="semester_id" bag="historyExport" />
                    @endif
                </label>

                <label>
                    Report Academic Year
                    <select name="academic_year" data-export-academic-year-select required>
                        <option value="">Choose academic year</option>
                        @foreach($academicYears as $academicYear)
                            <option value="{{ $academicYear }}" {{ $exportAcademicYear === $academicYear ? 'selected' : '' }}>
                                {{ $academicYear }}
                            </option>
                        @endforeach
                    </select>
                    @if($activeFormKey === 'history-export')
                        <x-field-error field="academic_year" bag="historyExport" />
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
        </section>
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
    <style>
        .history-filter-form,
        .history-export-form {
            grid-template-columns: minmax(190px, 1fr) minmax(190px, 1fr) auto auto;
        }

        .history-export-form {
            grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr) auto;
        }

        .history-group-card {
            display: grid;
            gap: 14px;
            padding: 16px;
            border: 1px solid #e4dacd;
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.66);
        }

        .history-group-header {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: center;
        }

        .history-group-header h2 {
            margin: 0;
            color: #173c66;
            font-size: 1.1rem;
        }

        .history-table {
            min-width: 940px;
        }

        .history-table td:last-child {
            text-align: left;
        }

        .history-reference {
            display: inline-flex;
            padding: 7px 11px;
            border-radius: 999px;
            background: #f3efe6;
            color: #173c66;
            font-size: 12px;
            font-weight: 800;
        }

        .history-program-name {
            margin-top: 7px;
            max-width: 28ch;
        }

        .history-step-details summary {
            cursor: pointer;
            color: #173c66;
            font-size: 13px;
            font-weight: 800;
        }

        .history-step-list {
            display: grid;
            gap: 8px;
            min-width: 220px;
            margin-top: 10px;
            padding: 10px;
            border-radius: 12px;
            background: #fffaf0;
            border: 1px solid #eadfce;
        }

        .history-step-list div {
            display: grid;
            gap: 3px;
        }

        .history-step-list span {
            color: #667085;
            font-size: 12px;
            line-height: 1.35;
        }

        .history-empty-state p,
        .report-validation-state p {
            margin: 6px 0 0;
        }

        .history-download-button {
            align-self: end;
            min-width: 220px;
        }

        @media (max-width: 980px) {
            .history-filter-form,
            .history-export-form {
                grid-template-columns: 1fr;
            }

            .history-download-button {
                width: 100%;
            }
        }

        @media (max-width: 720px) {
            .history-group-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const semesterSelect = document.querySelector('[data-export-semester-select]');
            const academicYearSelect = document.querySelector('[data-export-academic-year-select]');
            const semesterText = document.querySelector('[data-report-semester-text]');
            const academicYearText = document.querySelector('[data-report-academic-year-text]');

            if (!semesterSelect || !academicYearSelect) {
                return;
            }

            const updateReportSummary = function () {
                const selectedOption = semesterSelect.options[semesterSelect.selectedIndex];
                const academicYear = selectedOption?.dataset.academicYear || academicYearSelect.value || '';

                if (academicYear && selectedOption?.value) {
                    academicYearSelect.value = academicYear;
                }

                if (semesterText) {
                    semesterText.textContent = selectedOption?.value ? selectedOption.textContent.trim() : 'Choose semester';
                }

                if (academicYearText) {
                    academicYearText.textContent = academicYearSelect.value || 'Choose academic year';
                }
            };

            semesterSelect.addEventListener('change', updateReportSummary);
            academicYearSelect.addEventListener('change', updateReportSummary);
            updateReportSummary();
        });
    </script>
    @endpush
@endsection
