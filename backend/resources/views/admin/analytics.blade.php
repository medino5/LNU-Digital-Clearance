@extends('layouts.portal', [
    'title' => 'Analytics',
    'subtitle' => 'Review clearance performance and identify operational bottlenecks.',
])

@section('page')
    <div class="admin-page management-page analytics-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>Analytics</h1>
            </div>

            <a
                href="{{ route('admin.analytics.export', request()->query()) }}"
                class="management-primary-action"
            >
                Download Analytics Report
            </a>
        </section>

        <section class="admin-section-card management-card">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Report Filters</div>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.analytics.index') }}" class="analytics-filter-grid management-filter-grid">
                <label>
                    Program
                    <select name="program_code">
                        <option value="">All programs</option>
                        @foreach($programs as $program)
                            <option value="{{ $program->code }}" @selected($selectedProgramCode === $program->code)>
                                {{ $program->code }} - {{ $program->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Academic Year
                    <select name="academic_year">
                        <option value="">All academic years</option>
                        @foreach($academicYears as $academicYear)
                            <option value="{{ $academicYear }}" @selected($selectedAcademicYear === $academicYear)>
                                {{ $academicYear }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Semester
                    <select name="semester_id">
                        <option value="">All semesters</option>
                        @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" @selected((int) $selectedSemesterId === (int) $semester->id)>
                                {{ $semester->label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <button type="submit" class="button">Apply Filters</button>
                <a href="{{ route('admin.analytics.index') }}" class="button secondary management-secondary-action">Reset</a>
            </form>
        </section>

        <section class="analytics-summary-grid">
            @foreach($summary as $label => $value)
                <article class="analytics-metric-card">
                    <span>{{ $label }}</span>
                    <strong>{{ $value }}</strong>
                </article>
            @endforeach
        </section>

        <section class="analytics-grid">
            <article class="admin-section-card management-card">
                <div class="management-card-header">
                    <div>
                        <div class="eyebrow">Office Signing Performance</div>
                        <h2>Office signing time</h2>
                    </div>
                </div>

                @if($officePerformance->isEmpty())
                    <div class="empty-state">No approved office steps found for the selected filters.</div>
                @else
                    <div class="management-table-wrap">
                        <table class="management-table analytics-table">
                            <thead>
                                <tr>
                                    <th>Office</th>
                                    <th>Type</th>
                                    <th>Signed Steps</th>
                                    <th>Average</th>
                                    <th>Longest</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($officePerformance as $office)
                                    <tr>
                                        <td>
                                            <div class="table-main-text">{{ $office['office_label'] }}</div>
                                        </td>
                                        <td>{{ $office['office_type'] }}</td>
                                        <td>{{ number_format($office['signed_steps']) }}</td>
                                        <td><strong>{{ $office['avg_signing_time_label'] }}</strong></td>
                                        <td>{{ $office['max_signing_time_label'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </article>

            <article class="admin-section-card management-card">
                <div class="management-card-header">
                    <div>
                        <div class="eyebrow">Program Flow</div>
                        <h2>Student wait time by program</h2>
                    </div>
                </div>

                @if($programPerformance->isEmpty())
                    <div class="empty-state">No clearance data found for the selected filters.</div>
                @else
                    <div class="program-flow-list">
                        @foreach($programPerformance as $program)
                            <article class="program-flow-card">
                                <div>
                                    <strong>{{ $program['program_code'] }}</strong>
                                    <span>{{ $program['program_name'] }}</span>
                                </div>
                                <div class="program-flow-stats">
                                    <span>Completed: {{ number_format($program['completed_count']) }}</span>
                                    <span>Active: {{ number_format($program['in_progress_count']) }}</span>
                                    <span>Flagged: {{ number_format($program['flagged_count']) }}</span>
                                    <span>Avg: {{ $program['avg_completion_time_label'] }}</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
    <style>
        .analytics-filter-grid {
            grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) minmax(180px, 1fr) auto auto;
        }

        .analytics-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .analytics-metric-card {
            display: grid;
            gap: 10px;
            padding: 18px;
            border-radius: 18px;
            background: linear-gradient(135deg, #ffffff 0%, #fffaf0 100%);
            border: 1px solid #e4dacd;
            box-shadow: 0 10px 24px rgba(24, 58, 99, 0.05);
        }

        .analytics-metric-card span {
            color: #667085;
            font-size: 0.78rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .analytics-metric-card strong {
            color: #173c66;
            font-size: clamp(1.2rem, 2vw, 1.7rem);
            line-height: 1.15;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(360px, 0.8fr);
            gap: 18px;
            align-items: start;
        }

        .analytics-table {
            min-width: 760px;
        }

        .program-flow-list {
            display: grid;
            gap: 12px;
        }

        .program-flow-card {
            display: grid;
            gap: 12px;
            padding: 15px;
            border-radius: 16px;
            background: #ffffff;
            border: 1px solid #e4dacd;
        }

        .program-flow-card strong {
            color: #173c66;
            font-size: 1.05rem;
        }

        .program-flow-card span {
            color: #667085;
            line-height: 1.35;
        }

        .program-flow-card > div:first-child {
            display: grid;
            gap: 3px;
        }

        .program-flow-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .program-flow-stats span {
            display: inline-flex;
            padding: 7px 10px;
            border-radius: 999px;
            background: #f8f4ea;
            color: #173c66;
            font-size: 0.78rem;
            font-weight: 800;
        }

        @media (max-width: 1180px) {
            .analytics-grid,
            .analytics-summary-grid,
            .analytics-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @endpush
@endsection
