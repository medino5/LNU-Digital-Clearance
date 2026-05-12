@extends('layouts.portal', [
    'title' => 'Dashboard',
    'subtitle' => 'Start here to navigate programs, semesters, routing, accounts, reports, and analytics.',
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
                            <span class="quick-action-copy">Add or update student accounts.</span>
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
                            <span class="quick-action-copy">Approve mobile registrations.</span>
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
                            <span class="quick-action-copy">Set up staff signers.</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.routing.index') }}#designation-create" class="quick-action-card">
                        <span class="quick-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 4v16"/>
                                <path d="M18 4v16"/>
                                <path d="M6 7h8a4 4 0 0 1 0 8H6"/>
                                <path d="M14 15l4 4"/>
                            </svg>
                        </span>
                        <span class="quick-action-content">
                            <span class="quick-action-label">Manage Routing Offices</span>
                            <span class="quick-action-copy">Create routes and assign holders.</span>
                        </span>
                    </a>

                    <a href="{{ route('admin.clearance-history.index') }}" class="quick-action-card">
                        <span class="quick-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 4h14v16H5z"/>
                                <path d="M8 8h8"/>
                                <path d="M8 12h8"/>
                                <path d="M8 16h5"/>
                            </svg>
                        </span>
                        <span class="quick-action-content">
                            <span class="quick-action-label">Go to Download Reports</span>
                            <span class="quick-action-copy">Choose report filters.</span>
                        </span>
                    </a>
                </div>
            </div>
        </section>

        <section class="admin-section-card dashboard-snapshots" data-dashboard-snapshots>
            <div class="snapshot-header">
                <div>
                    <h1>System Snapshots</h1>
                    <p class="section-copy compact-copy" data-snapshot-scope>{{ $snapshotScopeLabel }}</p>
                </div>

                <div class="snapshot-filter-bar" aria-label="Snapshot filters">
                    <label>
                        <span>School Year</span>
                        <select data-snapshot-academic-year>
                            <option value="">All school years</option>
                            @foreach($academicYears as $academicYear)
                                <option value="{{ $academicYear }}">{{ $academicYear }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Semester</span>
                        <select data-snapshot-semester>
                            <option value="">All semesters</option>
                            @foreach($semesterOptions as $semester)
                                <option value="{{ $semester->id }}" data-academic-year="{{ $semester->academic_year }}">
                                    {{ $semester->label }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div class="snapshot-groups">
                <div class="snapshot-group">
                    <div class="snapshot-group-heading">
                        <span>Directory Setup</span>
                    </div>
                    <div class="snapshot-grid" data-snapshot-group="stable"></div>
                </div>

                <div class="snapshot-group">
                    <div class="snapshot-group-heading">
                        <span>Term Activity</span>
                    </div>
                    <div class="snapshot-grid activity-grid" data-snapshot-group="activity"></div>
                </div>
            </div>
        </section>

        <section class="dashboard-chart-grid">
            <div class="admin-section-card chart-panel">
                <div class="chart-panel-header">
                    <div>
                        <div class="eyebrow">Clearances Per Semester</div>
                        <h2 class="chart-title">Clearances Per Semester</h2>
                        <p class="section-copy compact-copy">Updates when the snapshot filters change.</p>
                    </div>
                </div>

                <div data-semester-chart></div>
            </div>

            <div class="admin-section-card chart-panel">
                <div class="chart-panel-header">
                    <div>
                        <div class="eyebrow">Clearance Status Distribution</div>
                        <h2 class="chart-title">Clearance Status Distribution</h2>
                        <p class="section-copy compact-copy">Filtered breakdown of in-progress, flagged, and completed clearances.</p>
                    </div>
                </div>

                <div data-status-chart></div>
            </div>
        </section>
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
    <style>
        .admin-page {
            display: grid;
            gap: 18px;
            font-size: 0.96rem;
        }

        .admin-section-card {
            display: grid;
            gap: 14px;
            padding: 18px 0;
            border-radius: 0;
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        .dashboard-intro-shell {
            display: grid;
            gap: 14px;
        }

        .dashboard-quick-actions {
            display: grid;
            grid-template-columns: minmax(190px, 0.45fr) minmax(0, 1fr);
            gap: 22px;
            align-items: start;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(23, 60, 102, 0.12);
        }

        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px 18px;
            align-items: stretch;
        }

        .quick-action-card {
            position: relative;
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 62px;
            padding: 8px 4px;
            border-radius: 0;
            text-decoration: none;
            color: #19324d;
            background: transparent;
            border: 0;
            border-bottom: 1px solid rgba(23, 60, 102, 0.1);
            box-shadow: none;
            cursor: pointer;
            transition: color 0.16s ease, border-color 0.16s ease, transform 0.16s ease;
        }

        .quick-action-card::after {
            content: "Open →";
            position: absolute;
            right: 4px;
            bottom: 50%;
            transform: translateY(50%);
            color: #173c66;
            font-size: 0.78rem;
            font-weight: 900;
            letter-spacing: 0.03em;
        }

        .quick-action-card:hover {
            transform: translateX(3px);
            box-shadow: none;
            border-color: rgba(210, 168, 61, 0.55);
            color: #0f2f52;
        }

        .quick-action-label {
            font-weight: 700;
            font-size: 1rem;
        }

        .quick-action-copy {
            font-size: 0.82rem;
            line-height: 1.35;
            color: #59657a;
        }

        .compact-copy {
            margin-top: 0;
            margin-bottom: 0;
            max-width: 60ch;
        }

        .snapshot-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
        }

        .snapshot-filter-bar {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .snapshot-filter-bar label {
            display: grid;
            gap: 5px;
            min-width: 180px;
            color: #173c66;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .snapshot-filter-bar select {
            min-height: 42px;
            border-radius: 12px;
            border: 1px solid #d8cbb9;
            background: #fff;
            color: #183a63;
            padding: 0 38px 0 12px;
            font-size: 0.92rem;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(24, 58, 99, 0.05);
        }

        .dashboard-snapshots.is-loading {
            opacity: 0.72;
        }

        .snapshot-groups {
            display: grid;
            gap: 16px;
        }

        .snapshot-group {
            display: grid;
            gap: 10px;
        }

        .snapshot-group-heading {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 12px;
            color: #173c66;
            font-weight: 800;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(23, 60, 102, 0.1);
        }

        .snapshot-group-heading small {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .snapshot-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(170px, 1fr));
            justify-content: center;
            gap: 12px;
        }

        .snapshot-grid.activity-grid {
            grid-template-columns: repeat(5, minmax(150px, 1fr));
        }

        .stat-tile {
            display: grid;
            justify-items: center;
            text-align: center;
            gap: 6px;
            padding: 15px 12px;
            background: rgba(255, 255, 255, 0.54);
            border: 1px solid rgba(23, 60, 102, 0.1);
            border-radius: 18px;
            min-height: 120px;
            box-shadow: 0 14px 34px rgba(24, 58, 99, 0.05);
        }

        .stat-tile .eyebrow {
            margin-bottom: 2px;
            font-size: 0.7rem;
            letter-spacing: 0.06em;
        }

        .metric-circle {
            width: 58px;
            height: 58px;
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

        .stat-hint {
            color: #64748b;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .dashboard-chart-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .chart-panel {
            align-content: start;
            min-height: 100%;
            padding: 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.58);
            border: 1px solid rgba(23, 60, 102, 0.1);
            box-shadow: 0 18px 42px rgba(24, 58, 99, 0.07);
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
            background: linear-gradient(180deg, #d2a83d 0%, #285892 54%, #173c66 100%);
            box-shadow: 0 8px 18px rgba(23, 60, 102, 0.2);
            transition: height 0.22s ease;
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
            box-shadow: inset 0 0 0 1px rgba(22, 56, 95, 0.08), 0 18px 36px rgba(24, 58, 99, 0.12);
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
            .dashboard-chart-grid {
                grid-template-columns: 1fr;
            }

            .quick-action-grid,
            .snapshot-grid,
            .snapshot-grid.activity-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 860px) {
            .status-chart-layout {
                grid-template-columns: 1fr;
            }

            .snapshot-header {
                display: grid;
            }

            .snapshot-filter-bar {
                justify-content: stretch;
            }

            .snapshot-filter-bar label {
                min-width: min(100%, 240px);
                flex: 1 1 180px;
            }
        }

        @media (max-width: 720px) {
            .snapshot-grid,
            .snapshot-grid.activity-grid {
                grid-template-columns: 1fr;
            }

            .quick-action-grid {
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

        .quick-action-label {
            font-weight: 700;
            font-size: 1rem;
        }

        .quick-action-copy {
            font-size: 0.92rem;
            line-height: 1.45;
            color: #59657a;
        }

        .quick-action-card::after {
            content: "Open ->";
        }
    </style>
    @endpush
@endsection

@push('scripts')
@php
    $initialSnapshotPayload = [
        'snapshotScopeLabel' => $snapshotScopeLabel,
        'snapshotStats' => $snapshotStats,
        'clearancesPerSemester' => $clearancesPerSemester,
        'semesterChartHasData' => $semesterChartHasData,
        'semesterChartMax' => $semesterChartMax,
        'statusChart' => $statusChart,
        'statusChartTotal' => $statusChartTotal,
        'statusChartHasData' => $statusChartHasData,
    ];
@endphp
<script>
document.addEventListener("DOMContentLoaded", function () {
    const snapshotRoot = document.querySelector("[data-dashboard-snapshots]");
    const stableGroup = document.querySelector('[data-snapshot-group="stable"]');
    const activityGroup = document.querySelector('[data-snapshot-group="activity"]');
    const semesterChart = document.querySelector("[data-semester-chart]");
    const statusChart = document.querySelector("[data-status-chart]");
    const scopeLabel = document.querySelector("[data-snapshot-scope]");
    const academicYearFilter = document.querySelector("[data-snapshot-academic-year]");
    const semesterFilter = document.querySelector("[data-snapshot-semester]");
    const snapshotEndpoint = @json(route('admin.dashboard.snapshots'));
    const initialSnapshot = @json($initialSnapshotPayload);
    let snapshotController = null;
    const snapshotCache = new Map();

    function formatNumber(value) {
        return new Intl.NumberFormat().format(Number(value || 0));
    }

    function renderStats(container, stats) {
        if (!container) return;

        container.replaceChildren(...(stats || []).map((stat) => {
            const card = document.createElement("div");
            card.className = "stat-card stat-tile";

            const label = document.createElement("div");
            label.className = "eyebrow";
            label.textContent = stat.label;

            const metric = document.createElement("div");
            metric.className = "metric-circle";
            const value = document.createElement("span");
            value.textContent = formatNumber(stat.value);
            metric.appendChild(value);

            const hint = document.createElement("div");
            hint.className = "stat-hint";
            hint.textContent = stat.hint || "";

            card.append(label, metric, hint);
            return card;
        }));
    }

    function renderSemesterChart(payload) {
        if (!semesterChart) return;

        const points = payload.clearancesPerSemester || [];
        if (!payload.semesterChartHasData) {
            semesterChart.innerHTML = `
                <div class="chart-empty-state">
                    <h5 class="fw-semibold">No clearance data yet</h5>
                    <p class="text-muted mb-3">No clearances match the selected period.</p>
                    <a href="{{ route('admin.students.index') }}" class="btn btn-primary btn-sm">Start by adding students</a>
                </div>
            `;
            return;
        }

        const max = Math.max(Number(payload.semesterChartMax || 1), 1);
        const wrapper = document.createElement("div");
        wrapper.className = "semester-chart";
        wrapper.setAttribute("role", "img");
        wrapper.setAttribute("aria-label", "Bar chart showing clearance counts per semester");

        points.forEach((point) => {
            const count = Number(point.count || 0);
            const height = count > 0 ? Math.max((count / max) * 100, 8) : 0;
            const group = document.createElement("div");
            group.className = "semester-bar-group";

            const value = document.createElement("div");
            value.className = "semester-bar-value";
            value.textContent = formatNumber(count);

            const track = document.createElement("div");
            track.className = "semester-bar-track";

            const fill = document.createElement("div");
            fill.className = "semester-bar-fill";
            fill.style.height = `${height}%`;
            fill.title = `${point.label}: ${formatNumber(count)} clearance${count === 1 ? "" : "s"}`;
            track.appendChild(fill);

            const label = document.createElement("div");
            label.className = "semester-bar-label";
            label.textContent = point.label;

            group.append(value, track, label);
            wrapper.appendChild(group);
        });

        semesterChart.replaceChildren(wrapper);
    }

    function renderStatusChart(payload) {
        if (!statusChart) return;

        const points = payload.statusChart || [];
        const total = Number(payload.statusChartTotal || 0);
        if (!payload.statusChartHasData || total === 0) {
            statusChart.innerHTML = `
                <div class="chart-empty-state">
                    <h5 class="fw-semibold">No clearance data yet</h5>
                    <p class="text-muted mb-3">No status records match the selected period.</p>
                    <a href="{{ route('admin.students.index') }}" class="btn btn-primary btn-sm">Start by adding students</a>
                </div>
            `;
            return;
        }

        let offset = 0;
        const segments = points.map((point) => {
            const percentage = total > 0 ? (Number(point.count || 0) / total) * 100 : 0;
            const segment = `${point.color} ${offset}% ${offset + percentage}%`;
            offset += percentage;
            return segment;
        });

        const layout = document.createElement("div");
        layout.className = "status-chart-layout";

        const donut = document.createElement("div");
        donut.className = "status-donut";
        donut.style.background = `conic-gradient(${segments.join(", ")})`;
        donut.setAttribute("role", "img");
        donut.setAttribute("aria-label", "Donut chart showing clearance status distribution");

        const hole = document.createElement("div");
        hole.className = "status-donut-hole";
        const totalText = document.createElement("span");
        totalText.className = "status-donut-total";
        totalText.textContent = formatNumber(total);
        const caption = document.createElement("span");
        caption.className = "status-donut-caption";
        caption.textContent = "Total";
        hole.append(totalText, caption);
        donut.appendChild(hole);

        const legend = document.createElement("div");
        legend.className = "status-legend";

        points.forEach((point) => {
            const count = Number(point.count || 0);
            const percentage = total > 0 ? Math.round((count / total) * 100) : 0;
            const item = document.createElement("div");
            item.className = "status-legend-item";

            const dot = document.createElement("span");
            dot.className = "status-dot";
            dot.style.backgroundColor = point.color;

            const copy = document.createElement("div");
            copy.className = "status-legend-copy";
            const label = document.createElement("strong");
            label.textContent = point.label;
            const detail = document.createElement("span");
            detail.textContent = `${formatNumber(count)} clearance${count === 1 ? "" : "s"} (${percentage}%)`;
            copy.append(label, detail);
            item.append(dot, copy);
            legend.appendChild(item);
        });

        layout.append(donut, legend);
        statusChart.replaceChildren(layout);
    }

    function renderSnapshot(payload) {
        if (scopeLabel) {
            scopeLabel.textContent = payload.snapshotScopeLabel || "All records";
        }

        renderStats(stableGroup, payload.snapshotStats?.stable);
        renderStats(activityGroup, payload.snapshotStats?.activity);
        renderSemesterChart(payload);
        renderStatusChart(payload);
    }

    function syncSemesterOptions() {
        if (!academicYearFilter || !semesterFilter) return;

        const selectedYear = academicYearFilter.value;
        Array.from(semesterFilter.options).forEach((option) => {
            if (!option.value) {
                option.hidden = false;
                option.disabled = false;
                return;
            }

            const matchesYear = !selectedYear || option.dataset.academicYear === selectedYear;
            option.hidden = !matchesYear;
            option.disabled = !matchesYear;
        });

        const selectedOption = semesterFilter.selectedOptions[0];
        if (selectedOption && selectedOption.disabled) {
            semesterFilter.value = "";
        }
    }

    async function loadSnapshot() {
        if (!academicYearFilter || !semesterFilter) return;

        syncSemesterOptions();

        if (snapshotController) {
            snapshotController.abort();
        }

        snapshotController = new AbortController();
        const params = new URLSearchParams();
        if (academicYearFilter.value) {
            params.set("academic_year", academicYearFilter.value);
        }
        if (semesterFilter.value) {
            params.set("semester_id", semesterFilter.value);
        }

        const cacheKey = params.toString() || "all";
        if (snapshotCache.has(cacheKey)) {
            renderSnapshot(snapshotCache.get(cacheKey));
            return;
        }

        snapshotRoot?.classList.add("is-loading");

        try {
            const response = await fetch(`${snapshotEndpoint}?${params.toString()}`, {
                headers: { "Accept": "application/json" },
                signal: snapshotController.signal,
            });

            if (!response.ok) {
                throw new Error("Snapshot request failed");
            }

            const payload = await response.json();
            snapshotCache.set(cacheKey, payload);
            renderSnapshot(payload);
        } catch (error) {
            if (error.name !== "AbortError") {
                renderSnapshot(initialSnapshot);
            }
        } finally {
            snapshotRoot?.classList.remove("is-loading");
        }
    }

    snapshotCache.set("all", initialSnapshot);
    renderSnapshot(initialSnapshot);
    syncSemesterOptions();
    academicYearFilter?.addEventListener("change", loadSnapshot);
    semesterFilter?.addEventListener("change", loadSnapshot);

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
