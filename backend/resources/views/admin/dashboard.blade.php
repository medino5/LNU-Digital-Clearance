@extends('layouts.portal', [
    'title' => 'Dashboard',
    'subtitle' => 'Start here to navigate programs, semesters, routing, accounts, and clearance history.',
])

@section('page')
    <div class="admin-page">
        @include('admin.partials.page-feedback')

        <section class="dashboard-intro-shell">
            <div class="admin-section-card dashboard-quick-actions">
                <div>
                    <h1>Get started</h1>
                    <p class="section-copy">Choose what you want to do next.</p>
                </div>

                <div class="quick-action-grid">
                    <a href="{{ route('admin.students.index') }}" class="quick-action-card">
                        <span class="quick-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z"/>
                                <path d="M4 21a8 8 0 0 1 16 0"/>
                            </svg>
                        </span>
                        <span class="quick-action-content">
                            <span class="quick-action-label">Create Student</span>
                            <span class="quick-action-copy">Open the students page to add and manage student accounts.</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.registration-requests.index') }}" class="quick-action-card">
                        <span class="quick-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M9 11l2 2 4-5"/>
                                <path d="M5 4h14v16H5z"/>
                            </svg>
                        </span>
                        <span class="quick-action-content">
                            <span class="quick-action-label">Review Requests</span>
                            <span class="quick-action-copy">Approve or reject mobile account registrations before login is allowed.</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.office-accounts.index') }}" class="quick-action-card">
                        <span class="quick-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M4 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/>
                                <path d="M8 7h2M8 11h2M8 15h2M14 7h2M14 11h2M14 15h2"/>
                                <path d="M3 21h18"/>
                            </svg>
                        </span>
                        <span class="quick-action-content">
                            <span class="quick-action-label">Create Office Account</span>
                            <span class="quick-action-copy">Open the office accounts page for staff account setup.</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.routing.index') }}" class="quick-action-card">
                        <span class="quick-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 4v16"/>
                                <path d="M18 4v16"/>
                                <path d="M6 7h8a4 4 0 0 1 0 8H6"/>
                                <path d="M14 15l4 4"/>
                            </svg>
                        </span>
                        <span class="quick-action-content">
                            <span class="quick-action-label">Assign Holders</span>
                            <span class="quick-action-copy">Open routing to review eligible users and update designation holders.</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.clearance-history.index') }}" class="quick-action-card quick-action-card--accent">
                        <span class="quick-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3v12"/>
                                <path d="M7 10l5 5 5-5"/>
                                <path d="M5 21h14"/>
                            </svg>
                        </span>
                        <span class="quick-action-content">
                            <span class="quick-action-label">Download Report</span>
                            <span class="quick-action-copy">Open completed clearance records for review, filtering, and export.</span>
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <section class="admin-section-card">
            <div>
                <h1>System Snapshots</h1>
            </div>

            <div class="grid-3">
                <div class="stat-card stat-tile">
                    <div class="eyebrow">Programs</div>
                    <div class="metric-circle">
                        <span>{{ $programCount }}</span>
                    </div>
                </div>

                <div class="stat-card stat-tile">
                    <div class="eyebrow">Semesters</div>
                    <div class="metric-circle">
                        <span>{{ $semesterCount }}</span>
                    </div>
                </div>

                <div class="stat-card stat-tile">
                    <div class="eyebrow">Students</div>
                    <div class="metric-circle">
                        <span>{{ $studentCount }}</span>
                    </div>
                </div>

                <div class="stat-card stat-tile">
                    <div class="eyebrow">Pending Registrations</div>
                    <div class="metric-circle">
                        <span>{{ $pendingRegistrationRequestCount }}</span>
                    </div>
                </div>

                <div class="stat-card stat-tile">
                    <div class="eyebrow">Office Accounts</div>
                    <div class="metric-circle">
                        <span>{{ $officeAccountCount }}</span>
                    </div>
                </div>

                <div class="stat-card stat-tile">
                    <div class="eyebrow">Completed Clearances</div>
                    <div class="metric-circle">
                        <span>{{ $completedClearanceCount }}</span>
                    </div>
                </div>

                <div class="stat-card stat-tile">
                    <div class="eyebrow">Routing Designations</div>
                    <div class="metric-circle">
                        <span>{{ $designationCount }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="dashboard-chart-grid">
            <div class="admin-section-card chart-panel">
                <div class="chart-panel-header">
                    <div>
                        <div class="eyebrow">Clearances Per Semester</div>
                        <h2 class="chart-title">Clearances Per Semester</h2>
                        <p class="section-copy compact-copy">Live counts from recorded clearances across your configured semesters.</p>
                    </div>
                </div>

                @if($semesterChartHasData)
                    <div class="semester-chart" role="img" aria-label="Bar chart showing clearance counts per semester">
                        @foreach($clearancesPerSemester as $semesterPoint)
                            @php
                                $heightRatio = $semesterPoint['count'] > 0
                                    ? max(($semesterPoint['count'] / $semesterChartMax) * 100, 8)
                                    : 0;
                            @endphp

                            <div class="semester-bar-group">
                                <div class="semester-bar-value">{{ $semesterPoint['count'] }}</div>
                                <div class="semester-bar-track">
                                    <div
                                        class="semester-bar-fill"
                                        style="height: {{ $heightRatio }}%;"
                                        title="{{ $semesterPoint['label'] }}: {{ $semesterPoint['count'] }} clearance{{ $semesterPoint['count'] === 1 ? '' : 's' }}"
                                    ></div>
                                </div>
                                <div class="semester-bar-label">{{ $semesterPoint['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="chart-empty-state">
                        <h5 class="fw-semibold">No clearance data yet</h5>
                        <p class="text-muted mb-3">
                            There are no recorded clearances for this section yet.
                        </p>

                        <a href="{{ route('admin.students.index') }}" class="btn btn-primary btn-sm">
                            Start by adding students
                        </a>
                    </div>
                @endif
            </div>

            <div class="admin-section-card chart-panel">
                <div class="chart-panel-header">
                    <div>
                        <div class="eyebrow">Clearance Status Distribution</div>
                        <h2 class="chart-title">Clearance Status Distribution</h2>
                        <p class="section-copy compact-copy">Current breakdown of in-progress, flagged, and completed clearances.</p>
                    </div>
                </div>

                @if($statusChartHasData)
                    @php
                        $statusSegments = [];
                        $statusOffset = 0;
                    @endphp

                    @foreach($statusChart as $statusPoint)
                        @php
                            $percentage = round(($statusPoint['count'] / $statusChartTotal) * 100, 2);
                            $statusSegments[] = $statusPoint['color'] . ' ' . $statusOffset . '% ' . ($statusOffset + $percentage) . '%';
                            $statusOffset += $percentage;
                        @endphp
                    @endforeach

                    <div class="status-chart-layout">
                        <div
                            class="status-donut"
                            style="background: conic-gradient({{ implode(', ', $statusSegments) }});"
                            role="img"
                            aria-label="Donut chart showing clearance status distribution"
                        >
                            <div class="status-donut-hole">
                                <span class="status-donut-total">{{ $statusChartTotal }}</span>
                                <span class="status-donut-caption">Total</span>
                            </div>
                        </div>

                        <div class="status-legend">
                            @foreach($statusChart as $statusPoint)
                                @php
                                    $statusPercentage = $statusChartTotal > 0
                                        ? round(($statusPoint['count'] / $statusChartTotal) * 100)
                                        : 0;
                                @endphp

                                <div class="status-legend-item">
                                    <span class="status-dot" style="background-color: {{ $statusPoint['color'] }};"></span>
                                    <div class="status-legend-copy">
                                        <strong>{{ $statusPoint['label'] }}</strong>
                                        <span>{{ $statusPoint['count'] }} clearance{{ $statusPoint['count'] === 1 ? '' : 's' }} ({{ $statusPercentage }}%)</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="chart-empty-state">
                        <h5 class="fw-semibold">No clearance data yet</h5>
                        <p class="text-muted mb-3">
                            There are no clearance records to display yet.
                        </p>

                        <a href="{{ route('admin.students.index') }}" class="btn btn-primary btn-sm">
                            Start by adding students
                        </a>
                    </div>
                @endif
            </div>
        </section>
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
    <style>
        .admin-page {
            display: grid;
            gap: 18px;
        }

        .admin-section-card {
            display: grid;
            gap: 18px;
            padding: 22px;
            border-radius: 18px;
            background: linear-gradient(135deg, #fbf7ef 0%, #fffdf8 100%);
            border: 1px solid #e8dfd1;
            box-shadow: 0 12px 28px rgba(24, 58, 99, 0.05);
        }

        .dashboard-intro-shell {
            display: grid;
            gap: 18px;
        }

        .dashboard-quick-actions {
            display: grid;
            gap: 20px;
        }

        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .quick-action-card {
            display: grid;
            gap: 8px;
            padding: 15px 16px;
            border-radius: 14px;
            text-decoration: none;
            color: #19324d;
            background: #ffffff;
            border: 1px solid #e3d9c9;
            box-shadow: 0 6px 16px rgba(24, 58, 99, 0.05);
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
        }

        .quick-action-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 12px 26px rgba(24, 58, 99, 0.1);
            border-color: #d4c0a6;
        }

        .quick-action-card--accent {
            background: linear-gradient(135deg, #173c66 0%, #27588f 100%);
            color: #f8f4ea;
            border-color: #173c66;
        }

        .quick-action-card--accent .quick-action-copy {
            color: rgba(248, 244, 234, 0.88);
        }

        .quick-action-label {
            font-weight: 700;
            font-size: 1rem;
        }

        .quick-action-copy {
            font-size: 0.92rem;
            line-height: 1.45;
            color: #59657a;
        }

        .compact-copy {
            margin-top: 0;
            margin-bottom: 0;
            max-width: 60ch;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            justify-content: center;
            gap: 16px;
        }

        .stat-tile {
            display: grid;
            justify-items: center;
            text-align: center;
            gap: 4px;
            padding: 14px 12px;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid #e4dacd;
            border-radius: 16px;
        }

        .stat-tile .eyebrow {
            margin-bottom: 2px;
            font-size: 0.7rem;
            letter-spacing: 0.06em;
        }

        .metric-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #173c66 0%, #27588f 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 6px 14px rgba(23, 60, 102, 0.2);
            margin-bottom: 6px;
        }

        .metric-circle span {
            line-height: 1;
        }

        .dashboard-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .chart-panel {
            align-content: start;
            min-height: 100%;
        }

        .chart-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }

        .chart-title {
            margin: 4px 0 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #173c66;
        }

        .semester-chart {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(84px, 1fr));
            gap: 16px;
            align-items: end;
            min-height: 290px;
            padding-top: 12px;
        }

        .semester-bar-group {
            display: grid;
            gap: 10px;
            justify-items: center;
            align-items: end;
        }

        .semester-bar-value {
            font-size: 0.82rem;
            color: #5b6679;
            font-weight: 600;
        }

        .semester-bar-track {
            width: 100%;
            max-width: 64px;
            height: 180px;
            border-radius: 999px;
            background: linear-gradient(180deg, #edf2f8 0%, #dbe5f1 100%);
            display: flex;
            align-items: flex-end;
            overflow: hidden;
            box-shadow: inset 0 0 0 1px rgba(22, 56, 95, 0.08);
        }

        .semester-bar-fill {
            width: 100%;
            border-radius: 999px;
            background: linear-gradient(180deg, #285892 0%, #173c66 100%);
            box-shadow: 0 8px 18px rgba(23, 60, 102, 0.18);
        }

        .semester-bar-label {
            font-size: 0.78rem;
            line-height: 1.4;
            color: #334155;
            text-align: center;
        }

        .status-chart-layout {
            display: grid;
            grid-template-columns: minmax(180px, 220px) minmax(0, 1fr);
            gap: 24px;
            align-items: center;
        }

        .status-donut {
            width: 220px;
            height: 220px;
            border-radius: 50%;
            position: relative;
            display: grid;
            place-items: center;
            margin: 0 auto;
            box-shadow: inset 0 0 0 1px rgba(22, 56, 95, 0.08);
        }

        .status-donut-hole {
            width: 118px;
            height: 118px;
            border-radius: 50%;
            background: #fffdf9;
            display: grid;
            place-items: center;
            text-align: center;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
        }

        .status-donut-total {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            color: #173c66;
            line-height: 1;
        }

        .status-donut-caption {
            display: block;
            margin-top: 6px;
            font-size: 0.82rem;
            color: #64748b;
        }

        .status-legend {
            display: grid;
            gap: 14px;
        }

        .status-legend-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: 16px;
            background: #fff;
            border: 1px solid #e7dfd2;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .status-legend-copy {
            display: grid;
            gap: 4px;
        }

        .status-legend-copy strong {
            color: #183a63;
        }

        .status-legend-copy span {
            color: #58657a;
            font-size: 0.92rem;
            line-height: 1.4;
        }

        .chart-empty-state {
            display: grid;
            place-items: center;
            min-height: 250px;
            border: 1px dashed #d5cbbd;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.7);
            color: #64748b;
            text-align: center;
            padding: 24px;
        }

        @media (max-width: 1100px) {
            .quick-action-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dashboard-chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 860px) {
            .status-chart-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .quick-action-grid {
                grid-template-columns: 1fr;
            }

            .grid-3 {
                grid-template-columns: 1fr;
            }

            .semester-chart {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .status-donut {
                width: 180px;
                height: 180px;
            }

            .status-donut-hole {
                width: 96px;
                height: 96px;
            }
        }

        .quick-action-card {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 17px 16px;
            border-radius: 14px;
            text-decoration: none;
            color: #19324d;
            background: #ffffff;
            border: 1px solid #e3d9c9;
            box-shadow: 0 6px 16px rgba(24, 58, 99, 0.05);
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
        }

        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 26px rgba(24, 58, 99, 0.1);
            border-color: #d4c0a6;
        }

        .quick-action-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(210, 168, 61, 0.16);
            color: #173c66;
            box-shadow: inset 0 0 0 1px rgba(210, 168, 61, 0.2);
        }

        .quick-action-icon svg {
            width: 22px;
            height: 22px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.9;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .quick-action-content {
            display: grid;
            gap: 7px;
        }

        .quick-action-card--accent {
            background: linear-gradient(135deg, #173c66 0%, #27588f 100%);
            color: #f8f4ea;
            border-color: #173c66;
        }

        .quick-action-card--accent .quick-action-icon {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
        }

        .quick-action-card--accent .quick-action-copy {
            color: rgba(248, 244, 234, 0.88);
        }

        .quick-action-label {
            font-weight: 700;
            font-size: 1rem;
        }

        .quick-action-copy {
            font-size: 0.92rem;
            line-height: 1.45;
            color: #59657a;
        }
    </style>
    @endpush
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("form").forEach(form => {
        form.addEventListener("submit", function () {
            const btn = form.querySelector("button[type=submit]");
            if (btn && !btn.disabled) {
                btn.disabled = true;

                const text = btn.querySelector(".btn-text");
                const spinner = btn.querySelector(".spinner-border");

                if (text) text.classList.add("d-none");
                if (spinner) spinner.classList.remove("d-none");
            }
        });
    });
});
</script>
@endpush
