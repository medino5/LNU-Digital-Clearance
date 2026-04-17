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
                    <h1>COMMON ADMIN ACTIONS</h1>
                    <p class="section-copy">Open the page you need without scrolling through one long admin workspace.</p>
                </div>

                <div class="quick-action-grid">
                    <a href="{{ route('admin.students.index') }}" class="quick-action-card">
                        <span class="quick-action-label">Create Student</span>
                        <span class="quick-action-copy">Open the students page to add and manage student accounts.</span>
                    </a>

                    <a href="{{ route('admin.office-accounts.index') }}" class="quick-action-card">
                        <span class="quick-action-label">Create Office Account</span>
                        <span class="quick-action-copy">Open the office accounts page for staff account setup.</span>
                    </a>

                    <a href="{{ route('admin.routing.index') }}" class="quick-action-card">
                        <span class="quick-action-label">Assign Holders</span>
                        <span class="quick-action-copy">Open routing to review eligible users and update designation holders.</span>
                    </a>

                    <a href="{{ route('admin.clearance-history.index') }}" class="quick-action-card quick-action-card--accent">
                        <span class="quick-action-label">Open Clearance History</span>
                        <span class="quick-action-copy">Open completed clearance records for review, filtering, and export.</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="admin-section-card">
            <div>
                <h1>SYSTEM SNAPSHOTS</h1>
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
    </div>

    @push('styles')
    <style>
        .dashboard-intro-shell {
            display: grid;
            gap: 18px;
        }

        .dashboard-quick-actions {
            display: grid;
            gap: 20px;
            padding: 24px;
            border-radius: 24px;
            background: linear-gradient(135deg, #fbf7ef 0%, #fffdf8 100%);
            border: 1px solid #e8dfd1;
        }

        .dashboard-quick-actions h2 {
            margin: 6px 0 8px;
        }

        .quick-action-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .quick-action-card {
            display: grid;
            gap: 8px;
            padding: 16px 18px;
            border-radius: 18px;
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
            margin-bottom: 14px;
            max-width: 60ch;
        }

        @media (max-width: 1100px) {
            .quick-action-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .quick-action-grid {
                grid-template-columns: 1fr;
            }
        }

        .metric-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #173c66 0%, #27588f 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 1.4rem;
            box-shadow: 0 6px 14px rgba(23, 60, 102, 0.2);
            margin-bottom: 10px;
        }

        .metric-circle span {
            line-height: 1;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(180px, 1fr));
            justify-content: center;   /* centers the whole grid */
            gap: 24px;
        }

        .admin-section-card .grid-3 {
            max-width: 800px;
            margin: 0 auto;
        }

        .stat-tile {
            display: grid;
            justify-items: center;
            text-align: center;
            gap: 4px; 
            padding: 16px 12px; 
        }

        .stat-tile .eyebrow {
            margin-bottom: 2px;
            font-size: 0.7rem;
            letter-spacing: 0.06em;
        }

        .metric-circle {
            width: 64px;   /* was 72px */
            height: 64px;
            font-size: 1.2rem;
            margin-bottom: 6px;
        }

        .stat-tile .metric-note {
            margin-top: 2px;
            font-size: 0.85rem;
            color: #6b7280; /* softer */
        }
    </style>
    @endpush
@endsection
