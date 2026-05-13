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

                <label>
                    Semester
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
                    Academic Year
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
        </section>
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
    <style>
        .history-export-form {
            grid-template-columns: minmax(200px, 1fr) minmax(200px, 1fr) minmax(240px, 1.2fr) auto;
        }

        .history-download-button {
            align-self: end;
            min-width: 220px;
        }

        .report-validation-state p {
            margin: 6px 0 0;
        }

        @media (max-width: 980px) {
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

            if (!semesterSelect || !academicYearSelect) {
                return;
            }

            const updateReportSummary = function () {
                const selectedOption = semesterSelect.options[semesterSelect.selectedIndex];
                const academicYear = selectedOption?.dataset.academicYear || academicYearSelect.value || '';

                if (academicYear && selectedOption?.value) {
                    academicYearSelect.value = academicYear;
                }
            };

            semesterSelect.addEventListener('change', updateReportSummary);
            academicYearSelect.addEventListener('change', updateReportSummary);
            updateReportSummary();
        });
    </script>
    @endpush
@endsection



