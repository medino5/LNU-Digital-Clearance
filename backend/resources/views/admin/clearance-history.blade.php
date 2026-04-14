@extends('layouts.portal', [
    'title' => 'Clearance History',
    'subtitle' => 'Filter completed clearances and export the Excel report.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))

    <div class="stack">
        @include('admin.partials.page-feedback')

        <div class="card history-panel">
            <div class="section-subheader">
                <div>
                    <div class="eyebrow">Clearance History</div>
                    <h3>Completed clearance records by semester and academic year</h3>
                    <p class="section-copy compact-copy">Review finished clearances and export a workbook grouped by program.</p>
                </div>
                <div class="toolbar" style="gap: 12px; align-items: flex-end;">
                    <form method="GET" action="{{ route('admin.clearance-history.index') }}" class="toolbar" style="gap: 12px; align-items: flex-end;">
                        <label class="history-filter">
                            <span class="mini">Academic Year</span>
                            <select name="history_academic_year" onchange="this.form.submit()">
                                <option value="">All academic years</option>
                                @foreach($academicYears as $academicYear)
                                    <option value="{{ $academicYear }}" {{ $selectedAcademicYear === $academicYear ? 'selected' : '' }}>
                                        {{ $academicYear }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label class="history-filter">
                            <span class="mini">Semester</span>
                            <select name="history_semester" onchange="this.form.submit()">
                                <option value="">All semesters</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ $selectedSemesterId === $semester->id ? 'selected' : '' }}>
                                        {{ $semester->label }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </form>

                    <form method="POST" action="{{ route('admin.clearance-reports.completed.export') }}" id="history-export-form">
                        @csrf
                        <input type="hidden" name="_form_key" value="history-export">
                        <label class="history-filter">
                            <span class="mini">Download Report</span>
                            <button type="submit" class="button history-download-button">Download Excel Report</button>
                        </label>
                        <input type="hidden" name="semester_id" value="{{ old('semester_id', $selectedSemesterId) }}">
                        <input type="hidden" name="academic_year" value="{{ old('academic_year', $selectedAcademicYear) }}">
                    </form>
                </div>
            </div>

            @if($activeFormKey === 'history-export')
                <div class="record" style="margin-bottom: 12px;">
                    <p class="mini" style="margin: 0 0 8px;">Export validation</p>
                    <x-field-error field="semester_id" bag="historyExport" />
                    <x-field-error field="academic_year" bag="historyExport" />
                </div>
            @endif

            @if($historyHasRecords)
                @foreach($history as $semesterLabel => $records)
                    <div class="record history-record">
                        <h3 style="margin-top: 0;">{{ $semesterLabel }}</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Program</th>
                                    <th>Reference</th>
                                    <th>Completed</th>
                                    <th>Signed Offices</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $clearance)
                                    <tr>
                                        <td>
                                            <strong>{{ $clearance->student_id_number }}</strong><br>
                                            {{ $clearance->student_name }}<br>
                                            <span class="mini">Year {{ $clearance->year_level }}</span>
                                        </td>
                                        <td>{{ $clearance->program_code }} - {{ $clearance->program_name }}</td>
                                        <td>{{ $clearance->reference_number }}</td>
                                        <td>{{ optional($clearance->completed_at)->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @foreach($clearance->steps as $step)
                                                <div class="mini">
                                                    {{ $step->office_label }}
                                                    @if($step->signed_at)
                                                        - {{ $step->signed_at->format('M d, Y h:i A') }}
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            @else
                <div class="record">
                    <p class="muted" style="margin: 0;">No completed clearances found for the selected semester and academic year filter.</p>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
    <style>
        .compact-copy {
            margin-top: 0;
            margin-bottom: 14px;
            max-width: 60ch;
        }

        .history-panel .section-subheader {
            gap: 18px;
            align-items: flex-end;
        }

        .history-download-button {
            min-width: 220px;
        }
    </style>
    @endpush
@endsection
