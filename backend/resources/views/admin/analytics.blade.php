@extends('layouts.portal', [
    'title' => 'Analytics',
    'subtitle' => 'Review clearance activity, signing speed, and operational bottlenecks.',
])

@section('page')
    @php
        $timelineMax = max(1, (int) ($requestsOverTime->max('requests') ?? 0));
        $officeMax = max(1, (int) ($officePerformance->max('total_steps') ?? 0));
        $programMax = max(1, (int) ($programPerformance->max('total_count') ?? 0));
        $statusItems = collect($statusDistribution);
        $completedPercent = (float) ($statusItems->firstWhere('label', 'Completed')['percent'] ?? 0);
        $pendingPercent = (float) ($statusItems->firstWhere('label', 'Pending')['percent'] ?? 0);
        $flaggedPercent = (float) ($statusItems->firstWhere('label', 'Flagged')['percent'] ?? 0);
        $donutGradient = $totals['requests'] > 0
            ? 'conic-gradient(var(--status-success-text) 0 ' . $completedPercent . '%, var(--status-warning-text) ' . $completedPercent . '% ' . ($completedPercent + $pendingPercent) . '%, var(--status-danger-text) ' . ($completedPercent + $pendingPercent) . '% 100%)'
            : 'conic-gradient(var(--border-subtle) 0 100%)';
    @endphp

    <div class="admin-page analytics-dashboard-page">
        @include('admin.partials.page-feedback')

        <section class="analytics-hero">
            <div>
                <span class="analytics-kicker">Performance</span>
                <h1>Analytics Dashboard</h1>
                <p>{{ $scopeLabel }}</p>
            </div>

            <form method="GET" action="{{ route('admin.analytics.index') }}" class="analytics-scope-form" data-analytics-filter-form>
                <input type="hidden" name="scope" value="school_year_semester">

                <label>
                    School Year
                    <select name="academic_year">
                        @foreach($academicYears as $academicYear)
                            <option value="{{ $academicYear }}" @selected($selectedAcademicYear === $academicYear)>
                                {{ $academicYear }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Semester
                    <select name="semester_term">
                        @foreach($semesterTerms as $value => $label)
                            <option value="{{ $value }}" @selected($selectedSemesterTerm === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

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

                <button type="submit">Apply</button>
                <a href="{{ route('admin.analytics.export', request()->query()) }}">Download Analytics Report</a>
            </form>
        </section>

        <section class="analytics-metric-grid">
            @foreach($summary as $metric)
                <article class="analytics-metric analytics-tone-{{ $metric['tone'] }}">
                    <div class="analytics-metric-icon">{{ strtoupper(substr($metric['label'], 0, 1)) }}</div>
                    <div>
                        <span>{{ $metric['label'] }}</span>
                        <strong>{{ $metric['value'] }}</strong>
                        <small>{{ $metric['detail'] }}</small>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="analytics-main-grid">
            <article class="analytics-panel analytics-wide-panel">
                <div class="analytics-panel-heading">
                    <h2>Clearance Requests Over Time</h2>
                    <span>{{ $scopeLabel }}</span>
                </div>

                @if($requestsOverTime->isEmpty())
                    <div class="analytics-empty">No clearance requests found for this filter.</div>
                @else
                    <div class="analytics-time-chart">
                        @foreach($requestsOverTime as $point)
                            @php
                                $requestHeight = max(8, ($point['requests'] / $timelineMax) * 100);
                                $completedHeight = max(6, ($point['completed'] / $timelineMax) * 100);
                            @endphp
                            <div class="analytics-time-column">
                                <div class="analytics-bar-stack">
                                    <span class="analytics-bar analytics-bar-requests" style="height: {{ $requestHeight }}%"></span>
                                    <span class="analytics-bar analytics-bar-completed" style="height: {{ $completedHeight }}%"></span>
                                </div>
                                <strong>{{ number_format($point['requests']) }}</strong>
                                <small>{{ $point['label'] }}</small>
                            </div>
                        @endforeach
                    </div>
                    <div class="analytics-legend">
                        <span><i class="legend-blue"></i>Requests</span>
                        <span><i class="legend-green"></i>Completed</span>
                    </div>
                @endif
            </article>

            <article class="analytics-panel">
                <div class="analytics-panel-heading">
                    <h2>Clearance Status Distribution</h2>
                </div>

                <div class="analytics-donut-wrap">
                    <div class="analytics-donut" style="--donut: {{ $donutGradient }}">
                        <strong>{{ number_format($totals['requests']) }}</strong>
                        <span>Total</span>
                    </div>

                    <div class="analytics-status-list">
                        @foreach($statusDistribution as $status)
                            <div>
                                <span style="--status-color: {{ $status['color'] }}"></span>
                                <p>{{ $status['label'] }}</p>
                                <strong>{{ $status['percent'] }}% ({{ number_format($status['count']) }})</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        </section>

        <section class="analytics-secondary-grid">
            <article class="analytics-panel">
                <div class="analytics-panel-heading">
                    <h2>Requests by Signer / Office</h2>
                </div>

                @if($officePerformance->isEmpty())
                    <div class="analytics-empty">No office steps found for this filter.</div>
                @else
                    <div class="analytics-bar-list">
                        @foreach($officePerformance as $office)
                            <div class="analytics-horizontal-row">
                                <div>
                                    <strong>{{ $office['office_label'] }}</strong>
                                    <span>{{ $office['office_type'] }}</span>
                                </div>
                                <div class="analytics-horizontal-track">
                                    <span style="width: {{ max(3, ($office['total_steps'] / $officeMax) * 100) }}%"></span>
                                </div>
                                <small>{{ number_format($office['total_steps']) }}</small>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="analytics-panel">
                <div class="analytics-panel-heading">
                    <h2>Average Clearance Time</h2>
                </div>

                <div class="analytics-average-card">
                    <div class="analytics-clock">T</div>
                    <strong>{{ $totals['avg_completion_label'] }}</strong>
                    <span>Based on completed clearances in the selected scope.</span>
                </div>

                @if($requestsOverTime->isNotEmpty())
                    <div class="analytics-mini-trend">
                        @foreach($requestsOverTime as $point)
                            <div>
                                <span>{{ $point['label'] }}</span>
                                <strong>{{ $point['avg_time_label'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="analytics-panel">
                <div class="analytics-panel-heading">
                    <h2>Clearance Completion Rate</h2>
                </div>

                <div class="analytics-rate">
                    <div class="analytics-rate-gauge" style="--rate: {{ min(100, max(0, $totals['completion_rate'])) }}%">
                        <strong>{{ number_format($totals['completion_rate'], 1) }}%</strong>
                    </div>
                    <span>{{ number_format($totals['completed']) }} of {{ number_format($totals['requests']) }} requests completed.</span>
                </div>
            </article>
        </section>

        <section class="analytics-bottom-grid">
            <article class="analytics-panel">
                <div class="analytics-panel-heading">
                    <h2>Office Delay Risk</h2>
                    <span>Weighted by waiting, flagged, and signing time</span>
                </div>

                @if($bottleneckSigners->isEmpty())
                    <div class="analytics-empty">No delay risk data found for this filter.</div>
                @else
                    <div class="analytics-bottleneck-list">
                        @foreach($bottleneckSigners as $index => $office)
                            @php
                                $riskWidth = min(100, max(8, ($office['delay_score'] ?? 0) * 8));
                            @endphp
                            <div>
                                <span>{{ $index + 1 }}</span>
                                <div>
                                    <strong>{{ $office['office_label'] }}</strong>
                                    <small>Risk score {{ $office['delay_score'] ?? 0 }} · avg signing {{ $office['avg_signing_time_label'] }}</small>
                                    <div class="analytics-risk-meter" aria-label="Delay risk score">
                                        <i style="width: {{ $riskWidth }}%"></i>
                                    </div>
                                    <em>{{ $office['pending_steps'] }} waiting · {{ $office['flagged_steps'] }} flagged</em>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="analytics-panel">
                <div class="analytics-panel-heading">
                    <h2>Program Flow</h2>
                </div>

                @if($programPerformance->isEmpty())
                    <div class="analytics-empty">No program activity found for this filter.</div>
                @else
                    <div class="analytics-program-list">
                        @foreach($programPerformance as $program)
                            <div>
                                <div>
                                    <strong>{{ $program['program_code'] }}</strong>
                                    <span>{{ $program['program_name'] }}</span>
                                </div>
                                <div class="analytics-program-track">
                                    <span style="width: {{ max(3, ($program['total_count'] / $programMax) * 100) }}%"></span>
                                </div>
                                <small>{{ number_format($program['total_count']) }} requests</small>
                            </div>
                        @endforeach
                    </div>
                @endif
            </article>
        </section>

        <section class="analytics-panel analytics-insights-panel">
            <div class="analytics-panel-heading">
                <h2>Recent Insights</h2>
            </div>

            <div class="analytics-insight-grid">
                @foreach($recentInsights as $insight)
                    <article class="analytics-insight analytics-tone-{{ $insight['tone'] }}">
                        <span class="analytics-insight-icon">{{ strtoupper(substr($insight['title'], 0, 1)) }}</span>
                        <div>
                            <strong>{{ $insight['title'] }}</strong>
                            <span>{{ $insight['body'] }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    </div>

    @push('styles')
    <style>
        .analytics-dashboard-page {
            display: grid;
            gap: 10px;
            min-width: 0;
        }

        .analytics-dashboard-page,
        .analytics-dashboard-page * {
            overflow-wrap: anywhere;
        }

        .analytics-hero,
        .analytics-panel,
        .analytics-metric {
            border: 1px solid rgba(23, 60, 102, 0.1);
            background: rgba(255, 255, 255, 0.94);
            box-shadow: 0 18px 42px rgba(23, 60, 102, 0.07);
            min-width: 0;
        }

        .analytics-hero {
            display: grid;
            grid-template-columns: minmax(260px, 1fr) minmax(360px, auto);
            gap: 12px;
            align-items: start;
            padding: 14px;
            border-radius: 18px;
            background:
                radial-gradient(circle at top left, rgba(241, 190, 72, 0.2), transparent 32%),
                linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-surface) 100%);
        }

        .analytics-kicker {
            color: var(--brand-gold);
            font-size: 0.74rem;
            font-weight: 900;
            letter-spacing: 0.13em;
            text-transform: uppercase;
        }

        .analytics-hero h1 {
            margin: 6px 0 4px;
            color: var(--text-primary);
            font-size: clamp(1.5rem, 2.4vw, 2.05rem);
            line-height: 1;
        }

        .analytics-hero p {
            margin: 0;
            color: var(--text-muted);
            font-weight: 700;
        }

        .analytics-scope-form {
            display: grid;
            grid-template-columns: repeat(3, minmax(130px, 1fr)) auto auto;
            gap: 8px;
            align-items: end;
            justify-self: end;
            width: min(100%, 860px);
            min-width: 0;
        }

        .analytics-scope-form label {
            display: grid;
            gap: 5px;
            color: var(--text-muted);
            font-size: 0.72rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            min-width: 0;
        }

        .analytics-scope-form select,
        .analytics-scope-form button,
        .analytics-scope-form a {
            min-height: 38px;
            border-radius: 12px;
            font: inherit;
            font-weight: 850;
        }

        .analytics-scope-form select {
            width: 100%;
            border: 1px solid var(--border-subtle);
            background: var(--bg-surface);
            color: var(--text-primary);
            padding: 0 12px;
        }

        .analytics-scope-form button,
        .analytics-scope-form a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            border: 0;
            text-decoration: none;
            cursor: pointer;
            min-width: 0;
            text-align: center;
            white-space: normal;
            line-height: 1.15;
        }

        .analytics-scope-form button {
            color: var(--bg-surface);
            background: var(--text-primary);
        }

        .analytics-scope-form a {
            color: var(--text-primary);
            background: var(--status-warning-bg);
        }

        .analytics-metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .analytics-metric {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px;
            align-items: center;
            padding: 12px;
            border-radius: 16px;
        }

        .analytics-metric-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            font-size: 1.2rem;
            font-weight: 950;
        }

        .analytics-metric span,
        .analytics-metric small {
            display: block;
            color: var(--text-muted);
        }

        .analytics-metric span {
            font-weight: 850;
        }

        .analytics-metric strong {
            display: block;
            margin: 5px 0;
            color: var(--text-primary);
            font-size: clamp(1.38rem, 2.5vw, 1.9rem);
            line-height: 1;
        }

        .analytics-metric small {
            font-weight: 700;
            line-height: 1.35;
        }

        .analytics-tone-blue .analytics-metric-icon,
        .analytics-insight.analytics-tone-blue {
            background: var(--status-info-bg);
            color: var(--status-info-text);
        }

        .analytics-tone-green .analytics-metric-icon,
        .analytics-insight.analytics-tone-green {
            background: var(--status-success-bg);
            color: var(--status-success-text);
        }

        .analytics-tone-purple .analytics-metric-icon,
        .analytics-insight.analytics-tone-purple {
            background: var(--status-info-bg);
            color: var(--status-info-text);
        }

        .analytics-tone-orange .analytics-metric-icon,
        .analytics-insight.analytics-tone-orange {
            background: var(--status-warning-bg);
            color: var(--status-warning-text);
        }

        .analytics-main-grid,
        .analytics-secondary-grid,
        .analytics-bottom-grid {
            display: grid;
            gap: 10px;
            align-items: stretch;
            min-width: 0;
        }

        .analytics-main-grid {
            grid-template-columns: minmax(0, 1.35fr) minmax(340px, 0.65fr);
        }

        .analytics-secondary-grid {
            grid-template-columns: minmax(0, 1fr) minmax(300px, 0.75fr) minmax(300px, 0.75fr);
        }

        .analytics-bottom-grid {
            grid-template-columns: minmax(320px, 0.8fr) minmax(0, 1.2fr);
        }

        .analytics-panel {
            padding: 12px;
            border-radius: 18px;
            overflow: hidden;
        }

        .analytics-panel-heading {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            align-items: start;
            margin-bottom: 10px;
        }

        .analytics-panel-heading h2 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.05rem;
            line-height: 1.2;
        }

        .analytics-panel-heading span {
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 750;
        }

        .analytics-time-chart {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(86px, 1fr));
            gap: 16px;
            align-items: end;
            min-height: 210px;
            padding: 8px 4px 0;
            border-bottom: 1px solid var(--border-subtle);
        }

        .analytics-time-column {
            display: grid;
            gap: 8px;
            justify-items: center;
            min-height: 200px;
        }

        .analytics-bar-stack {
            display: flex;
            gap: 5px;
            align-items: end;
            justify-content: center;
            height: 135px;
            width: 100%;
        }

        .analytics-bar {
            width: 18px;
            min-height: 8px;
            border-radius: 999px 999px 6px 6px;
        }

        .analytics-bar-requests {
            background: linear-gradient(180deg, var(--status-info-text), var(--status-info-bg));
        }

        .analytics-bar-completed {
            background: linear-gradient(180deg, var(--status-success-text), var(--status-success-bg));
        }

        .analytics-time-column strong {
            color: var(--status-info-text);
            font-size: 0.9rem;
        }

        .analytics-time-column small {
            color: var(--text-muted);
            font-weight: 800;
            text-align: center;
            line-height: 1.25;
        }

        .analytics-legend {
            display: flex;
            gap: 18px;
            margin-top: 10px;
            color: var(--text-muted);
            font-weight: 800;
        }

        .analytics-legend span {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .analytics-legend i,
        .analytics-status-list span {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }

        .legend-blue {
            background: var(--status-info-text);
        }

        .legend-green {
            background: var(--status-success-text);
        }

        .analytics-donut-wrap {
            display: grid;
            grid-template-columns: minmax(160px, 190px) minmax(0, 1fr);
            gap: 20px;
            align-items: center;
        }

        .analytics-donut {
            width: 160px;
            aspect-ratio: 1;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--donut);
            position: relative;
        }

        .analytics-donut::after {
            content: "";
            position: absolute;
            inset: 36px;
            border-radius: 50%;
            background: var(--bg-surface);
            box-shadow: inset 0 0 0 1px var(--border-subtle);
        }

        .analytics-donut strong,
        .analytics-donut span {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .analytics-donut strong {
            align-self: end;
            color: var(--text-primary);
            max-width: 88px;
            overflow: hidden;
            font-size: clamp(1rem, 1.6vw, 1.25rem);
            line-height: 1;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .analytics-donut span {
            align-self: start;
            color: var(--text-muted);
            font-weight: 800;
        }

        .analytics-status-list {
            display: grid;
            gap: 10px;
        }

        .analytics-status-list div {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 4px 10px;
            align-items: center;
            min-width: 0;
        }

        .analytics-status-list span {
            grid-row: span 2;
            background: var(--status-color);
        }

        .analytics-status-list p,
        .analytics-status-list strong {
            margin: 0;
        }

        .analytics-status-list p {
            color: var(--text-primary);
            font-weight: 850;
        }

        .analytics-status-list strong {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .analytics-bar-list,
        .analytics-program-list,
        .analytics-bottleneck-list,
        .analytics-mini-trend,
        .analytics-insight-grid {
            display: grid;
            gap: 8px;
        }

        .analytics-horizontal-row {
            display: grid;
            grid-template-columns: minmax(0, 0.75fr) minmax(120px, 1fr) auto;
            gap: 12px;
            align-items: center;
            min-width: 0;
        }

        .analytics-horizontal-row strong,
        .analytics-program-list strong {
            display: block;
            color: var(--text-primary);
            font-size: 0.92rem;
            overflow-wrap: anywhere;
        }

        .analytics-horizontal-row span,
        .analytics-program-list span,
        .analytics-horizontal-row small,
        .analytics-program-list small {
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 750;
            overflow-wrap: anywhere;
        }

        .analytics-horizontal-track,
        .analytics-program-track {
            height: 12px;
            overflow: hidden;
            border-radius: 999px;
            background: var(--border-subtle);
        }

        .analytics-horizontal-track span,
        .analytics-program-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--status-info-text), var(--status-success-text));
        }

        .analytics-average-card {
            display: grid;
            gap: 7px;
            justify-items: start;
            padding: 12px;
            border-radius: 16px;
            background: var(--bg-surface);
            color: var(--text-primary);
        }

        .analytics-clock {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: var(--status-info-bg);
            color: var(--status-info-text);
            font-weight: 950;
        }

        .analytics-average-card strong {
            font-size: 1.55rem;
            line-height: 1;
        }

        .analytics-average-card span {
            color: var(--text-muted);
            font-weight: 750;
            line-height: 1.35;
        }

        .analytics-mini-trend {
            margin-top: 12px;
        }

        .analytics-mini-trend div {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 9px 0;
            border-bottom: 1px solid var(--border-subtle);
            color: var(--text-muted);
            font-weight: 750;
        }

        .analytics-mini-trend strong {
            color: var(--text-primary);
        }

        .analytics-rate {
            display: grid;
            place-items: center;
            gap: 15px;
            min-height: 180px;
            text-align: center;
        }

        .analytics-rate-gauge {
            width: 160px;
            height: 84px;
            display: grid;
            place-items: end center;
            padding-bottom: 12px;
            border-radius: 190px 190px 0 0;
            background:
                radial-gradient(circle at 50% 100%, var(--bg-surface) 0 54%, transparent 55%),
                conic-gradient(from 270deg at 50% 100%, var(--status-success-text) 0 var(--rate), var(--border-subtle) var(--rate) 100%);
        }

        .analytics-rate-gauge strong {
            color: var(--text-primary);
            font-size: 1.8rem;
        }

        .analytics-rate > span {
            color: var(--text-muted);
            font-weight: 800;
        }

        .analytics-bottleneck-list div,
        .analytics-program-list > div {
            border: 1px solid var(--border-subtle);
            background: var(--bg-surface);
        }

        .analytics-bottleneck-list > div {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            align-items: center;
            padding: 12px;
            border-radius: 18px;
            min-width: 0;
            box-shadow: 0 10px 24px rgba(24, 58, 99, 0.05);
        }

        .analytics-bottleneck-list > div > span {
            width: 30px;
            height: 30px;
            display: grid;
            place-items: center;
            border-radius: 999px;
            background: var(--status-warning-bg);
            color: var(--status-warning-text);
            font-weight: 950;
        }

        .analytics-bottleneck-list strong {
            display: block;
            color: var(--text-primary);
        }

        .analytics-bottleneck-list small,
        .analytics-bottleneck-list em {
            display: block;
            color: var(--text-muted);
            font-weight: 750;
            font-style: normal;
            overflow-wrap: anywhere;
        }

        .analytics-bottleneck-list em {
            margin-top: 4px;
            font-size: 0.78rem;
        }

        .analytics-risk-meter {
            height: 7px;
            margin-top: 8px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(212, 165, 58, 0.18);
        }

        .analytics-risk-meter i {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--brand-gold), var(--status-danger-text));
        }

        .analytics-program-list > div {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(140px, 1.2fr) auto;
            gap: 12px;
            align-items: center;
            padding: 10px;
            border-radius: 16px;
            min-width: 0;
        }

        .analytics-insight-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .analytics-insight {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 10px;
            align-items: start;
            padding: 12px;
            border-radius: 16px;
            border: 1px solid rgba(23, 60, 102, 0.08);
            min-width: 0;
        }

        .analytics-insight-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.7);
            color: currentColor;
            font-weight: 950;
        }

        .analytics-insight strong {
            display: block;
            color: var(--text-primary);
            margin-bottom: 6px;
            line-height: 1.25;
        }

        .analytics-insight span {
            color: var(--text-muted);
            font-weight: 750;
            line-height: 1.4;
        }

        .analytics-insight .analytics-insight-icon {
            color: currentColor;
            line-height: 1;
        }

        .analytics-empty {
            padding: 28px;
            border: 1px dashed var(--border-subtle);
            border-radius: 18px;
            color: var(--text-muted);
            background: var(--bg-surface);
            text-align: center;
            font-weight: 800;
        }

        @media (max-width: 1320px) {
            .analytics-hero,
            .analytics-main-grid,
            .analytics-secondary-grid,
            .analytics-bottom-grid {
                grid-template-columns: 1fr;
            }

            .analytics-insight-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .analytics-metric-grid,
            .analytics-donut-wrap,
            .analytics-program-list > div,
            .analytics-horizontal-row,
            .analytics-scope-form,
            .analytics-insight-grid {
                grid-template-columns: 1fr;
            }

            .analytics-donut {
                margin: 0 auto;
            }
        }
    </style>
    @endpush

@endsection
