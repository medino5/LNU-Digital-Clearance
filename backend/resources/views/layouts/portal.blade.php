<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Student Clearance System' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-app: #F7F5EF;
            --bg-surface: #FFFFFF;
            --bg-sidebar: #0E2A47;
            --text-primary: #1F2937;
            --text-muted: #6B7280;
            --brand-navy: #16345C;
            --brand-gold: #D4A53A;
            --border-subtle: #E7E3D8;
            --status-success-bg: #DCFCE7;
            --status-success-text: #166534;
            --status-warning-bg: #FEF3C7;
            --status-warning-text: #92400E;
            --status-info-bg: #DBEAFE;
            --status-info-text: #1E40AF;
            --status-danger-bg: #FEE2E2;
            --status-danger-text: #991B1B;
            --card-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.04);
            --navy: var(--brand-navy);
            --navy-deep: var(--bg-sidebar);
            --gold: var(--brand-gold);
            --paper: var(--bg-app);
            --ink: var(--text-primary);
            --muted: var(--text-muted);
            --line: var(--border-subtle);
            --success: var(--status-success-text);
            --danger: var(--status-danger-text);
            --warning: var(--status-warning-text);
            --admin-sidebar-width: 208px;
        }

        * { box-sizing: border-box; }

        *, *::before, *::after {
            min-width: 0;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--bg-app);
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
            border-radius: 0.5rem;
            background: var(--brand-navy);
            color: var(--bg-surface);
            padding: 0.5rem 1rem;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, transform 0.15s ease;
            text-decoration: none;
        }

        button.secondary, .button.secondary {
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
            color: var(--text-primary);
        }

        button.secondary:hover,
        .button.secondary:hover {
            background: var(--bg-app);
        }

        button.warn,
        .button.warn,
        button.ghost,
        .button.ghost {
            background: var(--bg-surface);
            border: 1px solid var(--status-danger-bg);
            color: var(--status-danger-text);
        }

        button.warn:hover,
        .button.warn:hover,
        button.ghost:hover,
        .button.ghost:hover {
            background: var(--status-danger-bg);
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
            overflow-wrap: anywhere;
        }

        input, select, textarea {
            width: 100%;
            max-width: 100%;
            height: 2.5rem;
            border: 1px solid var(--border-subtle);
            border-radius: 0.5rem;
            padding: 0.55rem 0.75rem;
            font: inherit;
            background: var(--bg-surface);
            color: var(--ink);
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--brand-navy);
            outline: 2px solid rgba(22, 52, 92, 0.20);
            outline-offset: 0;
        }

        textarea {
            min-height: 88px;
            resize: vertical;
            overflow-wrap: anywhere;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 1rem 0.75rem;
            border-bottom: 1px solid var(--border-subtle);
            text-align: left;
            vertical-align: top;
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            font-weight: 500;
        }

        tbody tr:hover td {
            background: rgba(248, 250, 252, 0.6);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.125rem 0.625rem;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge.awaiting_action,
        .badge.waiting { background: var(--status-warning-bg); color: var(--status-warning-text); }
        .badge.approved,
        .badge.assigned,
        .badge.completed { background: var(--status-success-bg); color: var(--status-success-text); }
        .badge.flagged { background: var(--status-danger-bg); color: var(--status-danger-text); }
        .badge.in_progress { background: var(--status-info-bg); color: var(--status-info-text); }
        .badge.active { background: rgba(212, 165, 58, 0.18); color: var(--status-warning-text); }

        .callout {
            border-radius: 18px;
            padding: 14px 16px;
            border: 1px solid transparent;
            overflow-wrap: anywhere;
        }

        .callout.success { background: var(--status-success-bg); color: var(--success); border-color: var(--status-success-bg); }
        .callout.error { background: var(--status-danger-bg); color: var(--danger); border-color: var(--status-danger-bg); }

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
        .mini {
            font-size: 13px;
            color: var(--muted);
            overflow-wrap: anywhere;
        }
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
            background: var(--border-subtle);
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
            width: var(--admin-sidebar-width);
            height: 100vh;
            background: var(--bg-sidebar);
            color: var(--bg-surface);
            display: flex;
            flex-direction: column;
            padding: 16px 12px;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            z-index: 1000;
            box-shadow: 6px 0 22px rgba(14, 39, 66, 0.16);
        }

        .admin-sidebar::-webkit-scrollbar {
            display: none;
        }

        .sidebar-header {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            margin-bottom: 14px;
            padding: 8px 0 12px 0;
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
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .sidebar-brand-text {
            margin-top: 8px;
            font-size: 10px;
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
            gap: 5px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 11px;
            border-radius: 0 11px 11px 0;
            border-left: 3px solid transparent;
            text-decoration: none;
            color: rgba(255,255,255,0.72);
            transition: 0.2s ease;
            font-size: 13px;
            font-weight: 600;
        }

        .nav-item:hover {
            color: var(--bg-surface);
            background: rgba(255,255,255,0.05);
        }

        .nav-item.active {
            background: rgba(212, 165, 58, 0.15);
            border-left-color: var(--brand-gold);
            color: var(--bg-surface);
            font-weight: 500;
            box-shadow: none;
        }

        .nav-icon {
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* ================= MAIN ================= */

        .admin-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: var(--admin-sidebar-width);
        }

        .admin-header {
            position: fixed;
            top: 0;
            left: var(--admin-sidebar-width);
            right: 0;
            background:
                linear-gradient(135deg, var(--bg-sidebar) 0%, var(--brand-navy) 65%, var(--brand-navy) 100%);
            color: white;
            padding: 12px 20px;
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
            color: var(--bg-surface);
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
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 520px) minmax(0, 1fr);
            align-items: center;
            gap: 12px;
        }

        .admin-action-search {
            position: relative;
            width: 100%;
            grid-column: 2;
            justify-self: center;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .admin-header.is-search-hidden .admin-action-search:not(:focus-within) {
            opacity: 0;
            pointer-events: none;
            transform: translateY(-14px);
        }

        .admin-action-search-input {
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.28);
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            color: white;
            padding: 10px 42px 10px 16px;
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
            background: var(--bg-surface);
            border: 1px solid var(--border-subtle);
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
            color: var(--text-primary);
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
            color: var(--text-muted);
            font-size: 12px;
            line-height: 1.35;
        }

        .admin-action-search-empty {
            padding: 12px 14px;
            color: var(--text-muted);
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
            :root {
                --admin-sidebar-width: 198px;
            }

            .admin-sidebar {
                padding-inline: 10px;
            }

            .admin-header {
                align-items: stretch;
            }

            .admin-header-tools {
                width: 100%;
                grid-template-columns: minmax(0, 1fr) auto;
            }

            .admin-action-search {
                grid-column: 1;
                width: 100%;
            }

            .admin-user {
                grid-column: 2;
                align-self: flex-end;
            }
        }

        @media (max-width: 1180px) {
            .admin-current-page p {
                display: none;
            }
        }

        .admin-user {
            position: relative;
            flex: 0 0 auto;
            grid-column: 3;
            justify-self: end;
        }

        .admin-user-menu summary {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            min-height: 42px;
            padding: 4px 5px 4px 12px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.11);
            color: white;
            cursor: pointer;
            list-style: none;
            white-space: nowrap;
        }

        .admin-user-menu summary::-webkit-details-marker {
            display: none;
        }

        .admin-user-name {
            max-width: 180px;
            overflow: hidden;
            font-size: 13px;
            font-weight: 750;
            text-overflow: ellipsis;
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
            overflow: hidden;
            font-size: 14px;
            font-weight: 700;
        }

        .admin-user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .admin-user-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            z-index: 1120;
            width: min(260px, calc(100vw - 32px));
            display: grid;
            gap: 12px;
            padding: 14px;
            border: 1px solid var(--border-subtle);
            border-radius: 18px;
            background: var(--bg-surface);
            color: var(--text-primary);
            box-shadow: 0 20px 44px rgba(14, 39, 66, 0.22);
        }

        .admin-user-dropdown strong,
        .admin-user-dropdown span {
            display: block;
            overflow-wrap: anywhere;
        }

        .admin-user-dropdown span {
            margin-top: 3px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
        }

        .admin-user-dropdown form {
            margin: 0;
        }

        .admin-user-dropdown button {
            width: 100%;
            min-height: 40px;
            display: inline-flex;
            align-items: center;
            padding: 10px 14px;
            justify-content: center;
        }

        .admin-content {
            padding: 78px 18px 24px;
            background: var(--bg-app);
            min-height: 100vh;
            font-size: 14px;
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

        .admin-page-header h1,
        .management-header h1,
        .routing-page-header h1,
        .admin-page > h1,
        .dashboard-quick-actions h1,
        .snapshot-header h1,
        h1 {
            margin: 0;
            color: var(--text-primary);
            font-size: 1.875rem;
            line-height: 2.25rem;
            font-weight: 600;
            letter-spacing: -0.025em;
            text-transform: none;
        }

        h2, h3 {
            font-weight: 600;
            color: var(--text-primary);
        }
    </style>
    @stack('styles')
</head>
<body>

@if(auth()->check() && request()->routeIs('admin.*'))
    @php
        $currentAdmin = auth()->user();
        $currentAdminName = $currentAdmin?->name ?? 'Admin';
        $currentAdminPhoto = $currentAdmin?->profilePhotoUrl();
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
                'title' => 'Designations',
                'description' => 'Review designation scopes and assignment rules.',
                'url' => route('admin.routing.index') . '#routing-configuration',
                'keywords' => ['routing', 'route', 'designation', 'designations', 'configuration'],
            ],
            [
                'title' => 'Create Designation',
                'description' => 'Add VPSD, librarian, adviser, or student-led signer designations.',
                'url' => route('admin.routing.index') . '#designation-create',
                'keywords' => ['create routing office', 'add routing office', 'create designation', 'add designation', 'vpsd', 'librarian', 'adviser', 'treasurer'],
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
                <a href="{{ route('admin.dashboard') }}" class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 13h8V3H3v10Z"/><path d="M13 21h8V11h-8v10Z"/><path d="M13 3v6h8V3h-8Z"/><path d="M3 21h8v-6H3v6Z"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.programs.index') }}" class="nav-item {{ request()->routeIs('admin.programs.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>
                    <span>Programs</span>
                </a>
                <a href="{{ route('admin.semesters.index') }}" class="nav-item {{ request()->routeIs('admin.semesters.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 2v4"/><path d="M16 2v4"/><path d="M3 10h18"/><path d="M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>
                    <span>Semesters</span>
                </a>
                <a href="{{ route('admin.routing.index') }}" class="nav-item {{ request()->routeIs('admin.routing.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3v6a3 3 0 0 0 3 3h6a3 3 0 0 1 3 3v6"/><path d="M6 21v-6a3 3 0 0 1 3-3"/><path d="M18 3v6"/></svg>
                    <span>Designations</span>
                </a>
                <a href="{{ route('admin.students.index') }}" class="nav-item {{ request()->routeIs('admin.students.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-8 0v2"/><path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M20 8v6"/><path d="M23 11h-6"/></svg>
                    <span>Students</span>
                </a>
                <a href="{{ route('admin.registration-requests.index') }}" class="nav-item {{ request()->routeIs('admin.registration-requests.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 12 2 2 4-5"/><path d="M5 4h14v16H5z"/></svg>
                    <span>Registration Requests</span>
                </a>
                <a href="{{ route('admin.office-accounts.index') }}" class="nav-item {{ request()->routeIs('admin.office-accounts.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16"/><path d="M8 7h2M8 11h2M14 7h2M14 11h2"/><path d="M3 21h18"/></svg>
                    <span>Office Accounts</span>
                </a>
                <a href="{{ route('admin.analytics.index') }}" class="nav-item {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    <span>Analytics</span>
                </a>
                <a href="{{ route('admin.clearance-history.index') }}" class="nav-item {{ request()->routeIs('admin.clearance-history.*') || request()->routeIs('admin.clearances.show') ? 'active' : '' }}">
                    <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg>
                    <span>Download Reports</span>
                </a>
            </nav>
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
                        <details class="admin-user-menu">
                            <summary aria-label="Open admin account menu">
                                <span class="admin-user-name">{{ $currentAdminName }}</span>
                                <span class="admin-user-avatar">
                                    @if($currentAdminPhoto)
                                        <img src="{{ $currentAdminPhoto }}" alt="{{ $currentAdminName }} profile picture">
                                    @else
                                        {{ strtoupper(substr($currentAdminName, 0, 1)) }}
                                    @endif
                                </span>
                            </summary>

                            <div class="admin-user-dropdown">
                                <div>
                                    <strong>{{ $currentAdminName }}</strong>
                                    <span>{{ $currentAdmin?->username }}</span>
                                </div>

                                <form method="POST" action="{{ route('portal.logout') }}">
                                    @csrf
                                    <button type="submit">Log Out</button>
                                </form>
                            </div>
                        </details>
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
        form.addEventListener('submit', function (event) {
            const submitButton = event.submitter || form.querySelector('[data-loading-button], button[type="submit"], input[type="submit"]');

            if (form.dataset.isSubmitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.isSubmitting = 'true';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = submitButton.dataset.loadingText || 'Processing...';
            }
        });
    });

    @if(auth()->check() && request()->routeIs('admin.*'))
        const adminActionSearchItems = @json($adminActionSearchItems);
        const adminActionSearch = document.querySelector('[data-admin-action-search]');
        const adminActionSearchInput = document.querySelector('[data-admin-action-search-input]');
        const adminActionSearchResults = document.querySelector('[data-admin-action-search-results]');
        const adminHeader = document.querySelector('.admin-header');
        let adminActionActiveIndex = 0;
        let adminActionRenderedLinks = [];
        let lastAdminScrollY = window.scrollY || 0;

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
            const showAdminSearch = function () {
                adminHeader?.classList.remove('is-search-hidden');
            };

            const hideAdminSearch = function () {
                if (!adminActionSearch.matches(':focus-within')) {
                    adminHeader?.classList.add('is-search-hidden');
                }
            };

            adminActionSearchInput.addEventListener('input', renderAdminActionResults);

            adminActionSearchInput.addEventListener('focus', function () {
                showAdminSearch();

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

            window.addEventListener('scroll', function () {
                const currentScrollY = window.scrollY || 0;

                if (currentScrollY <= 80 || currentScrollY < lastAdminScrollY) {
                    showAdminSearch();
                } else if (currentScrollY > lastAdminScrollY + 8) {
                    hideAdminSearch();
                }

                lastAdminScrollY = currentScrollY;
            }, { passive: true });

            document.addEventListener('mousemove', function (event) {
                if (event.clientY <= 72) {
                    showAdminSearch();
                }
            }, { passive: true });
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
