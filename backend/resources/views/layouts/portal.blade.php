<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Student Clearance System' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --navy: #16385f;
            --navy-deep: #0e2742;
            --gold: #d2a83d;
            --paper: #fcfbf7;
            --ink: #1b1b1b;
            --muted: #667085;
            --line: #d7d3c8;
            --success: #1f7a4f;
            --danger: #b5442c;
            --warning: #8b5a14;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(210, 168, 61, 0.18), transparent 32%),
                linear-gradient(180deg, #f8f5ed 0%, #efe8d7 100%);
            color: var(--ink);
        }

        a { color: inherit; }

        .page-shell {
            min-height: 100vh;
            padding: 28px 18px 40px;
        }

        .panel {
            max-width: 1240px;
            margin: 0 auto;
            background: rgba(252, 251, 247, 0.95);
            border: 1px solid rgba(14, 39, 66, 0.14);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(14, 39, 66, 0.12);
        }

        .page-shell-flat {
            min-height: 100vh;
            width: 100%;
            padding: 28px 32px 40px;
        }

        .topbar {
            background: linear-gradient(135deg, var(--navy-deep), var(--navy));
            color: white;
            padding: 26px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .topbar h1 { margin: 0; font-size: 28px; letter-spacing: 0.02em; }
        .topbar p { margin: 6px 0 0; color: rgba(255, 255, 255, 0.78); }

        .content { padding: 28px; }
        .stack { display: grid; gap: 24px; }
        .grid-2 { display: grid; gap: 20px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { display: grid; gap: 16px; grid-template-columns: repeat(3, minmax(0, 1fr)); }

        .card {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 20px;
        }

        .card h2, .card h3 { margin-top: 0; }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .metric {
            font-size: 34px;
            color: var(--navy);
            font-weight: 700;
            margin: 0;
        }

        .metric-note { color: var(--muted); margin-top: 8px; }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }

        .topbar .toolbar {
            justify-content: flex-end;
        }

        .button, button, input[type="submit"] {
            border: none;
            border-radius: 999px;
            background: var(--navy);
            color: white;
            padding: 12px 18px;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
            transition: transform 0.15s ease;
            text-decoration: none;
        }

        button.secondary, .button.secondary {
            background: transparent;
            border: 1px solid var(--navy);
            color: var(--navy);
        }

        .topbar-form {
            display: inline-flex;
            gap: 0;
            margin: 0;
        }

        .topbar-action,
        .topbar-action:visited {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.72);
            color: white;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        .topbar-action:hover,
        .topbar-action:focus-visible {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            outline: 2px solid rgba(255, 255, 255, 0.2);
            outline-offset: 2px;
        }

        button.warn { background: var(--danger); }
        button:hover, input[type="submit"]:hover { transform: translateY(-1px); }

        form { display: grid; gap: 14px; }
        .field-grid { display: grid; gap: 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }

        label {
            display: grid;
            gap: 6px;
            font-size: 14px;
            color: var(--navy-deep);
        }

        .field-error {
            color: var(--danger);
            font-size: 13px;
            line-height: 1.4;
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid #cfc7b7;
            border-radius: 12px;
            padding: 11px 13px;
            font: inherit;
            background: white;
            color: var(--ink);
        }

        textarea { min-height: 88px; resize: vertical; }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--line);
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .badge.awaiting_action { background: #fff3d9; color: var(--warning); }
        .badge.approved, .badge.completed { background: #dff3e7; color: var(--success); }
        .badge.flagged { background: #fbe2dc; color: var(--danger); }
        .badge.in_progress { background: #dde8f7; color: var(--navy); }
        .badge.active { background: rgba(210, 168, 61, 0.18); color: #76510e; }

        .callout {
            border-radius: 18px;
            padding: 14px 16px;
            border: 1px solid transparent;
        }

        .callout.success { background: #eef9f2; color: var(--success); border-color: #bfe5cb; }
        .callout.error { background: #fff1ed; color: var(--danger); border-color: #f4c8be; }

        .list { display: grid; gap: 14px; }

        .record {
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 16px;
            background: white;
        }

        details.record summary { cursor: pointer; font-weight: 700; color: var(--navy); }
        details.record summary::-webkit-details-marker { display: none; }

        .muted { color: var(--muted); }
        .divider { height: 1px; background: var(--line); margin: 8px 0 18px; }
        .actions-inline { display: flex; flex-wrap: wrap; gap: 10px; }
        .mini { font-size: 13px; color: var(--muted); }
        /* New: section-based dashboard information architecture styles */
        /* Updated: stronger separation between major dashboard sections */
        .dashboard-section {
            display: grid;
            gap: 18px;
            margin-top: 32px;
            padding-top: 18px;
            border-top: 1px solid rgba(22, 56, 95, 0.18); /* soft navy */
        }

        /* Updated: stronger section header visual hierarchy */
        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 16px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(22, 56, 95, 0.22); /* slightly stronger navy */
        }

        .section-heading h2 {
            font-size: 22px;
            letter-spacing: 0.01em;
        }

        .section-copy {
            margin-top: 6px;
        }

        .section-heading h2,
        .section-subheader h3 {
            margin: 0;
        }

        .section-copy {
            margin: 8px 0 0;
            color: var(--muted);
            max-width: 760px;
        }

        .section-stack {
            display: grid;
            gap: 20px;
            align-content: start;
        }

        .stat-card {
            min-height: 168px;
        }

        /* make whole card clickable */
        .clickable-card {
            display: block;
            text-decoration: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }

        .clickable-card:hover,
        .clickable-card:focus-visible {
            transform: translateY(-3px);
            box-shadow: 0 14px 28px rgba(14, 39, 66, 0.12);
            border-color: rgba(22, 56, 95, 0.35);
            outline: none;
        }

        /* the "Manage" pill button */
        .manage-pill {
            display: inline-block;
            margin-top: 14px;
            padding: 6px 16px;
            border-radius: 999px;
            background: linear-gradient(180deg, var(--navy) 0%, var(--navy-deep) 100%);
            color: white;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            box-shadow: 0 4px 10px rgba(14, 39, 66, 0.18);
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        /* when hovering the card, button reacts */
        .clickable-card:hover .manage-pill {
            transform: scale(1.08);
            box-shadow: 0 6px 14px rgba(14, 39, 66, 0.22);
        }

        @media (max-width: 980px) {
            .grid-2, .grid-3, .field-grid { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; }
            .section-heading,
            .section-subheader {
                flex-direction: column;
                align-items: flex-start;
            }
            .history-filter {
                min-width: 100%;
            }
        }

        /* Fix: keep checkbox inline with label in forms */
        .inline-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .inline-check input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        /* New: scrollable list container for long records */
        .scrollable-list {
            max-height: 480px;
            overflow-y: auto;
            padding-right: 6px;
            position: relative;
        }

        /* optional: nicer scrollbar */
        .scrollable-list::-webkit-scrollbar {
            width: 6px;
        }

        .scrollable-list::-webkit-scrollbar-thumb {
            background: rgba(14, 39, 66, 0.2);
            border-radius: 999px;
        }

        #routing-configuration .section-subheader {
            margin-bottom: 14px;
        }

        #routing-configuration .list {
            margin-top: 8px;
        }

        #routing-configuration .record {
            padding: 18px 20px;
        }

        #routing-configuration .record + .record {
            margin-top: 12px;
        }

        .routing-list {
            max-height: 520px;
            overflow-y: auto;
        }

        .routing-list::-webkit-scrollbar {
            width: 10px;
        }

        .routing-list::-webkit-scrollbar-thumb {
            background: #c7bca8;
            border-radius: 999px;
        }

        .routing-list::-webkit-scrollbar-track {
            background: transparent;
        }

        /* ================= ADMIN SHELL ================= */

        .admin-layout {
            display: flex;
            min-height: 100vh;
        }

        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 238px;
            height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(210, 168, 61, 0.25), transparent 35%),
                linear-gradient(180deg, #081a2b 0%, #16385f 50%, #2a5d94 100%);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 18px 14px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 8px 0 24px rgba(14, 39, 66, 0.18);
        }

        .sidebar-header {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 18px;
            padding: 10px 0 14px 0;
            position: relative;
            text-align: center;
        }

        .sidebar-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            width: 70%;
            height: 1px;
            background: rgba(255,255,255,0.12);
        }

        .sidebar-logo {
            width: 82px;
            height: 82px;
            object-fit: contain;
        }

        .sidebar-brand-text {
            margin-top: 10px;
            font-size: 12px;
            font-weight: 800;  
            letter-spacing: 0.12em; 
            color: rgba(255, 255, 255, 0.95);
            text-align: center;
        }

        .sidebar-title {
            font-weight: 700;
            font-size: 16px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-item {
            padding: 11px 13px;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            transition: 0.2s ease;
            font-size: 14px;
            font-weight: 600;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-item.active {
            background: linear-gradient(180deg, #d9b24a 0%, #c89d2d 100%);
            color: #111;
            font-weight: 700;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.12);
        }

        .sidebar-logout {
            margin-top: auto;
            padding-top: 18px;
        }

        .sidebar-logout button {
            width: 100%;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* ================= MAIN ================= */

        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: 238px;
        }

        .admin-header {
            position: fixed;
            top: 0;
            left: 238px;
            right: 0;
            background:
                linear-gradient(135deg, #0e2742 0%, #16385f 65%, #1b4675 100%);
            color: white;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            z-index: 999;
            box-shadow: 0 4px 18px rgba(14, 39, 66, 0.10);
        }

        .admin-current-page {
            min-width: 190px;
            display: grid;
            gap: 2px;
        }

        .admin-current-page span {
            color: rgba(255, 255, 255, 0.72);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.13em;
            text-transform: uppercase;
        }

        .admin-current-page strong {
            color: #ffffff;
            font-size: 18px;
            line-height: 1.2;
        }

        .admin-current-page p {
            margin: 0;
            max-width: 34ch;
            color: rgba(255, 255, 255, 0.68);
            font-size: 12px;
            line-height: 1.35;
        }

        .admin-header-tools {
            flex: 1;
            min-width: 0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 14px;
        }

        .admin-action-search {
            position: relative;
            width: min(520px, 44vw);
        }

        .admin-action-search-input {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: white;
            padding: 12px 44px 12px 18px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
        }

        .admin-action-search-input::placeholder {
            color: rgba(255, 255, 255, 0.72);
        }

        .admin-action-search-input:focus {
            outline: 2px solid rgba(210, 168, 61, 0.6);
            outline-offset: 2px;
            border-color: rgba(210, 168, 61, 0.72);
            background: rgba(255, 255, 255, 0.18);
        }

        .admin-action-search-mark {
            position: absolute;
            top: 50%;
            right: 16px;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: rgba(255, 255, 255, 0.72);
            pointer-events: none;
        }

        .admin-action-search-mark svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .admin-action-search-results {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            right: 0;
            display: none;
            max-height: 360px;
            overflow-y: auto;
            padding: 8px;
            border-radius: 18px;
            background: #fffdf8;
            border: 1px solid #e4dacd;
            box-shadow: 0 20px 44px rgba(14, 39, 66, 0.22);
            z-index: 1100;
        }

        .admin-action-search-results.is-open {
            display: grid;
            gap: 6px;
        }

        .admin-action-search-result {
            display: grid;
            gap: 4px;
            padding: 12px 14px;
            border-radius: 12px;
            color: #183a63;
            text-decoration: none;
        }

        .admin-action-search-result:hover,
        .admin-action-search-result:focus-visible,
        .admin-action-search-result.is-active {
            background: rgba(210, 168, 61, 0.16);
            outline: none;
        }

        .admin-action-search-result strong {
            font-size: 14px;
        }

        .admin-action-search-result span {
            color: #667085;
            font-size: 12px;
            line-height: 1.35;
        }

        .admin-action-search-empty {
            padding: 12px 14px;
            color: #667085;
            font-size: 13px;
        }

        .admin-main [id] {
            scroll-margin-top: 130px;
        }

        .admin-focus-target {
            animation: adminTargetPulse 1.8s ease;
            outline: 3px solid rgba(210, 168, 61, 0.72);
            outline-offset: 4px;
        }

        @keyframes adminTargetPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(210, 168, 61, 0.38);
            }

            100% {
                box-shadow: 0 0 0 18px rgba(210, 168, 61, 0);
            }
        }

        @media (max-width: 1024px) {
            .admin-sidebar {
                width: 220px;
            }

            .admin-main {
                margin-left: 220px;
            }

            .admin-header {
                left: 220px;
                align-items: stretch;
            }

            .admin-header-tools {
                width: 100%;
            }

            .admin-action-search {
                width: 100%;
            }

            .admin-user {
                align-self: flex-end;
            }
        }

        @media (max-width: 1180px) {
            .admin-current-page p {
                display: none;
            }
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            white-space: nowrap;
        }

        .admin-user-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.28);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
        }

        .admin-content {
            padding: 96px 24px 28px;
            background:
                radial-gradient(circle at top right, rgba(210, 168, 61, 0.10), transparent 22%),
                linear-gradient(180deg, #f8f5ed 0%, #f1ebdf 100%);
            min-height: 100vh;
        }

        .align-stretch {
            align-items: stretch;
        }

        .full-height {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .flex-grow {
            flex: 1;
        }

        h1 {
            font-weight: 900;
        }

        h2, h3 {
            font-weight: 600;
        }
    </style>
    @stack('styles')
</head>
<body>

@if(auth()->check() && request()->routeIs('admin.*'))
    @php
        $adminActionSearchItems = [
            [
                'title' => 'Dashboard',
                'description' => 'Open the super admin overview and system snapshots.',
                'url' => route('admin.dashboard'),
                'keywords' => ['home', 'overview', 'stats', 'statistics', 'summary'],
            ],
            [
                'title' => 'Students',
                'description' => 'Open the student roster and account tools.',
                'url' => route('admin.students.index') . '#student-records',
                'keywords' => ['student', 'students', 'roster', 'accounts', 'search students'],
            ],
            [
                'title' => 'Add Student',
                'description' => 'Create a new student account.',
                'url' => route('admin.students.index') . '#student-create-card',
                'keywords' => ['add student', 'create student', 'new student', 'student account'],
            ],
            [
                'title' => 'Search Students',
                'description' => 'Find students by name, ID, program, or year level.',
                'url' => route('admin.students.index') . '#student-records',
                'keywords' => ['search student', 'find student', 'student filter', 'student lookup'],
            ],
            [
                'title' => 'Registration Requests',
                'description' => 'Review mobile student account requests before accounts are created.',
                'url' => route('admin.registration-requests.index'),
                'keywords' => ['registration', 'requests', 'pending accounts', 'approve student', 'mobile signup'],
            ],
            [
                'title' => 'Programs',
                'description' => 'Manage program codes, names, and organizations.',
                'url' => route('admin.programs.index') . '#program-records',
                'keywords' => ['program', 'programs', 'course', 'organization', 'org'],
            ],
            [
                'title' => 'Add Program',
                'description' => 'Create a new program entry.',
                'url' => route('admin.programs.index') . '#program-create-card',
                'keywords' => ['add program', 'create program', 'new program', 'program code'],
            ],
            [
                'title' => 'Semesters',
                'description' => 'Manage active and historical clearance periods.',
                'url' => route('admin.semesters.index') . '#semester-records',
                'keywords' => ['semester', 'semesters', 'academic year', 'school year', 'period'],
            ],
            [
                'title' => 'Add Semester',
                'description' => 'Create a new semester and academic year.',
                'url' => route('admin.semesters.index') . '#semester-create-card',
                'keywords' => ['add semester', 'create semester', 'new semester', 'active semester'],
            ],
            [
                'title' => 'Routing Configuration',
                'description' => 'Review designation routing and assignment rules.',
                'url' => route('admin.routing.index') . '#routing-configuration',
                'keywords' => ['routing', 'route', 'designation', 'designations', 'configuration'],
            ],
            [
                'title' => 'Assign Designation Holder',
                'description' => 'Assign eligible users to active office designations.',
                'url' => route('admin.routing.index') . '#routing-configuration',
                'keywords' => ['assign', 'assign holder', 'designation holder', 'office holder'],
            ],
            [
                'title' => 'Office Accounts',
                'description' => 'Open staff office account records.',
                'url' => route('admin.office-accounts.index') . '#office-records',
                'keywords' => ['office account', 'office accounts', 'staff', 'signer', 'officer'],
            ],
            [
                'title' => 'Add Office Account',
                'description' => 'Create a new staff office account.',
                'url' => route('admin.office-accounts.index') . '#office-account-create-card',
                'keywords' => ['add office account', 'create office account', 'new office account', 'staff account'],
            ],
            [
                'title' => 'Analytics',
                'description' => 'Review office signing speed and program bottlenecks.',
                'url' => route('admin.analytics.index'),
                'keywords' => ['analytics', 'performance', 'slow office', 'bottleneck', 'signing speed'],
            ],
            [
                'title' => 'Download Reports',
                'description' => 'Download completed clearance reports by semester and academic year.',
                'url' => route('admin.clearance-history.index') . '#download-reports-panel',
                'keywords' => ['download reports', 'reports', 'completed clearance', 'completed clearances'],
            ],
            [
                'title' => 'Download Excel Report',
                'description' => 'Export completed clearances as an Excel workbook.',
                'url' => route('admin.clearance-history.index') . '#history-export-form',
                'keywords' => ['excel', 'report', 'download', 'export', 'xlsx', 'clearance report'],
            ],
        ];
    @endphp
    <div class="admin-layout">

        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="{{ asset('images/lnu-logo.png') }}" class="sidebar-logo">
                <div class="sidebar-brand-text">DIGITAL CLEARANCE</div>
            </div>

            <nav class="sidebar-nav">
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('admin.programs.index') }}" class="nav-item {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}">Programs</a>
                <a href="{{ route('admin.semesters.index') }}" class="nav-item {{ request()->routeIs('admin.semesters.*') ? 'active' : '' }}">Semesters</a>
                <a href="{{ route('admin.routing.index') }}" class="nav-item {{ request()->routeIs('admin.routing.*') ? 'active' : '' }}">Routing</a>
                <a href="{{ route('admin.students.index') }}" class="nav-item {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">Students</a>
                <a href="{{ route('admin.registration-requests.index') }}" class="nav-item {{ request()->routeIs('admin.registration-requests.*') ? 'active' : '' }}">Registration Requests</a>
                <a href="{{ route('admin.office-accounts.index') }}" class="nav-item {{ request()->routeIs('admin.office-accounts.*') ? 'active' : '' }}">Office Accounts</a>
                <a href="{{ route('admin.analytics.index') }}" class="nav-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">Analytics</a>
                <a href="{{ route('admin.clearance-history.index') }}" class="nav-item {{ request()->routeIs('admin.clearance-history.*') || request()->routeIs('admin.clearances.show') ? 'active' : '' }}">Download Reports</a>
            </nav>

            <form method="POST" action="{{ route('portal.logout') }}" class="sidebar-logout">
                @csrf
                <button type="submit">Log Out</button>
            </form>
        </aside>

        <main class="admin-main">
            <header class="admin-header">

                <div class="admin-header-tools">
                    <div class="admin-action-search" data-admin-action-search>
                        <input
                            type="search"
                            class="admin-action-search-input"
                            data-admin-action-search-input
                            aria-label="Search admin actions"
                            aria-controls="admin-action-search-results"
                            autocomplete="off"
                            placeholder="Search admin actions"
                        >
                        <span class="admin-action-search-mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M10.5 18a7.5 7.5 0 1 1 5.3-12.8A7.5 7.5 0 0 1 10.5 18Z"/>
                                <path d="M16 16l5 5"/>
                            </svg>
                        </span>

                        <div
                            id="admin-action-search-results"
                            class="admin-action-search-results"
                            data-admin-action-search-results
                            role="listbox"
                        ></div>
                    </div>

                    <div class="admin-user">
                        <span>{{ auth()->user()->name }}</span>
                        <div class="admin-user-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </header>

            <div class="admin-content">
                @yield('page')
            </div>
        </main>

    </div>
@else
    @if(
        request()->routeIs('portal.login') ||
        request()->routeIs('admin.login') ||
        request()->routeIs('office.login')
    )
        @yield('page')
    @elseif(request()->routeIs('office.dashboard'))
        <div class="page-shell page-shell-flat">
            @yield('page')
        </div>
    @else
        <div class="page-shell">
            <div class="panel">
                @yield('page')
            </div>
        </div>
    @endif
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        const submitButton = form.querySelector('[data-loading-button]');

        if (!submitButton) {
            return;
        }

        form.addEventListener('submit', function (event) {
            if (form.dataset.isSubmitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.isSubmitting = 'true';
            submitButton.disabled = true;
            submitButton.textContent =
                submitButton.dataset.loadingText || 'Processing...';
        });
    });

    @if(auth()->check() && request()->routeIs('admin.*'))
        const adminActionSearchItems = @json($adminActionSearchItems);
        const adminActionSearch = document.querySelector('[data-admin-action-search]');
        const adminActionSearchInput = document.querySelector('[data-admin-action-search-input]');
        const adminActionSearchResults = document.querySelector('[data-admin-action-search-results]');
        let adminActionActiveIndex = 0;
        let adminActionRenderedLinks = [];

        const normalizeAdminActionSearch = function (value) {
            return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
        };

        const indexedAdminActionItems = adminActionSearchItems.map(function (item) {
            return {
                ...item,
                searchText: normalizeAdminActionSearch([
                    item.title,
                    item.description,
                    ...(item.keywords || []),
                ].join(' ')),
            };
        });

        const closeAdminActionResults = function () {
            adminActionSearchResults.classList.remove('is-open');
            adminActionSearchResults.innerHTML = '';
            adminActionRenderedLinks = [];
            adminActionActiveIndex = 0;
        };

        const setAdminActionActiveResult = function (index) {
            adminActionActiveIndex = index;
            adminActionRenderedLinks.forEach(function (link, linkIndex) {
                link.classList.toggle('is-active', linkIndex === adminActionActiveIndex);
                link.setAttribute('aria-selected', linkIndex === adminActionActiveIndex ? 'true' : 'false');
            });
        };

        const renderAdminActionResults = function () {
            const query = normalizeAdminActionSearch(adminActionSearchInput.value);

            adminActionSearchResults.innerHTML = '';
            adminActionRenderedLinks = [];
            adminActionActiveIndex = 0;

            if (!query) {
                closeAdminActionResults();
                return;
            }

            const queryWords = query.split(' ');
            const matches = indexedAdminActionItems
                .filter(function (item) {
                    return queryWords.every(function (word) {
                        return item.searchText.includes(word);
                    });
                })
                .slice(0, 8);

            adminActionSearchResults.classList.add('is-open');

            if (matches.length === 0) {
                const emptyResult = document.createElement('div');
                emptyResult.className = 'admin-action-search-empty';
                emptyResult.textContent = 'No matching admin action found.';
                adminActionSearchResults.appendChild(emptyResult);
                return;
            }

            matches.forEach(function (item, index) {
                const resultLink = document.createElement('a');
                const resultTitle = document.createElement('strong');
                const resultDescription = document.createElement('span');

                resultLink.className = 'admin-action-search-result';
                resultLink.href = item.url;
                resultLink.setAttribute('role', 'option');
                resultLink.setAttribute('aria-selected', index === 0 ? 'true' : 'false');

                resultTitle.textContent = item.title;
                resultDescription.textContent = item.description;

                resultLink.appendChild(resultTitle);
                resultLink.appendChild(resultDescription);
                adminActionSearchResults.appendChild(resultLink);
                adminActionRenderedLinks.push(resultLink);
            });

            setAdminActionActiveResult(0);
        };

        if (adminActionSearch && adminActionSearchInput && adminActionSearchResults) {
            adminActionSearchInput.addEventListener('input', renderAdminActionResults);

            adminActionSearchInput.addEventListener('focus', function () {
                if (adminActionSearchInput.value.trim() !== '') {
                    renderAdminActionResults();
                }
            });

            adminActionSearchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAdminActionResults();
                    adminActionSearchInput.blur();
                    return;
                }

                if (!adminActionRenderedLinks.length) {
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setAdminActionActiveResult((adminActionActiveIndex + 1) % adminActionRenderedLinks.length);
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setAdminActionActiveResult(
                        (adminActionActiveIndex - 1 + adminActionRenderedLinks.length) % adminActionRenderedLinks.length
                    );
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.location.href = adminActionRenderedLinks[adminActionActiveIndex].href;
                }
            });

            document.addEventListener('click', function (event) {
                if (!adminActionSearch.contains(event.target)) {
                    closeAdminActionResults();
                }
            });
        }

        const highlightAdminHashTarget = function () {
            const targetId = decodeURIComponent(window.location.hash.replace('#', ''));

            if (!targetId) {
                return;
            }

            const target = document.getElementById(targetId);

            if (!target) {
                return;
            }

            target.classList.add('admin-focus-target');
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });

            setTimeout(function () {
                target.classList.remove('admin-focus-target');
            }, 2200);
        };

        highlightAdminHashTarget();
        window.addEventListener('hashchange', highlightAdminHashTarget);
    @endif
});
</script>
@stack('scripts')
</body>
</html>
