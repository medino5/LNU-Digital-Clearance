<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Student Clearance System' }}</title>
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
            font-family: Georgia, "Times New Roman", serif;
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

        @media (max-width: 980px) {
            .grid-2, .grid-3, .field-grid { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <div class="panel">
            @yield('page')
        </div>
    </div>
</body>
</html>
