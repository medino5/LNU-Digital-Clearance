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
    @php($exportSemester = $semesters->firstWhere('id', (int) $exportSemesterId))

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>Download Reports</h1>
                <p>Generate the completed clearance Excel workbook for a specific semester and school year. Student-by-student clearance history stays in the office archive section.</p>
            </div>
        </section>

        <section class="admin-section-card management-card report-export-card" id="download-reports-panel">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Excel Export</div>
                    <h2>Completed clearance workbook</h2>
                    <p class="management-card-kicker">Choose the exact semester and academic year before downloading. The workbook includes the summary sheet and per-program sheets.</p>
                </div>
            </div>

            <div class="report-guidance-grid">
                <div class="report-guidance-card">
                    <strong>Use this for</strong>
                    <span>Official completed-clearance reporting by semester.</span>
                </div>
                <div class="report-guidance-card">
                    <strong>Not shown here</strong>
                    <span>Individual clearance history is now kept in the office archive flow.</span>
                </div>
                <div class="report-guidance-card">
                    <strong>Format</strong>
                    <span>Downloadable Excel workbook with summary and program sheets.</span>
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
        .report-guidance-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .report-guidance-card {
            display: grid;
            gap: 6px;
            padding: 15px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #e4dacd;
            color: #183a63;
        }

        .report-guidance-card strong {
            font-size: 0.82rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .report-guidance-card span {
            color: #667085;
            line-height: 1.45;
        }

        .history-export-form {
            grid-template-columns: minmax(220px, 1fr) minmax(220px, 1fr) auto;
        }

        .history-download-button {
            align-self: end;
            min-width: 220px;
        }

        .report-validation-state p {
            margin: 6px 0 0;
        }

        @media (max-width: 980px) {
            .report-guidance-grid,
            .history-export-form {
                grid-template-columns: 1fr;
            }

            .history-download-button {
                width: 100%;
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
