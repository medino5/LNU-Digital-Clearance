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

        @media (max-width: 1024px) {
            .admin-sidebar {
                width: 220px;
            }

            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
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
            width: 260px;
            height: 100vh;
            background: linear-gradient(180deg, var(--navy-deep), var(--navy));
            color: white;
            display: flex;
            flex-direction: column;
            padding: 24px 18px;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 26px;
            padding: 16px 0 12px 0;
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
            width: 120px;
            height: 120px;
            object-fit: contain;
        }

        .sidebar-brand-text {
            margin-top: 12px;
            font-size: 16px;   
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
            gap: 8px;
        }

        .nav-item {
            padding: 12px 14px;
            border-radius: 12px;
            text-decoration: none;
            color: white;
            transition: 0.2s ease;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.1);
        }

        .nav-item.active {
            background: var(--gold);
            color: #000;
            font-weight: 600;
        }

        .sidebar-logout {
            margin-top: auto;
        }

        .sidebar-logout button {
            width: 100%;
            background: rgba(255,255,255,0.15);
        }

        /* ================= MAIN ================= */

        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: 260px;
        }

        .admin-header {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            background: linear-gradient(135deg, var(--navy-deep), var(--navy));
            color: white;
            padding: 22px 28px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            z-index: 999;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
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
            padding: 110px 28px 28px;
            background: #f8f5ed;
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
                <a href="{{ route('admin.office-accounts.index') }}" class="nav-item {{ request()->routeIs('admin.office-accounts.*') ? 'active' : '' }}">Office Accounts</a>
                <a href="{{ route('admin.clearance-history.index') }}" class="nav-item {{ request()->routeIs('admin.clearance-history.*') || request()->routeIs('admin.clearances.show') ? 'active' : '' }}">Clearance History</a>
            </nav>

            <form method="POST" action="{{ route('portal.logout') }}" class="sidebar-logout">
                @csrf
                <button type="submit">Log Out</button>
            </form>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div class="admin-user">
                    <span>{{ auth()->user()->name }}</span>
                    <div class="admin-user-avatar">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                </div>
            </header>

            <div class="admin-content">
                @yield('page')
            </div>
        </main>

    </div>
@else
    @if(request()->routeIs('portal.login') || request()->routeIs('admin.login') || request()->routeIs('office.login'))
        @yield('page')
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
});
</script>
@stack('scripts')
</body>
</html>
