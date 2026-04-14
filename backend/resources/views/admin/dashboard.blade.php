@extends('layouts.portal', [
    'title' => 'Dashboard',
    'subtitle' => 'Start here to navigate programs, semesters, routing, accounts, and clearance history.',
])

@section('page')
    <div class="stack">
        @include('admin.partials.page-feedback')

        <section class="dashboard-intro-shell">
            <div class="card dashboard-quick-actions">
                <div>
                    <div class="eyebrow">Most Used</div>
                    <h2>Common admin actions</h2>
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
                        <span class="quick-action-label">Download Report</span>
                        <span class="quick-action-copy">Open clearance history to filter records and export the Excel report.</span>
                    </a>
                </div>
            </div>
        </section>

        <section class="dashboard-section" style="margin-top: 0;">
            <div class="section-heading">
                <div>
                    <h2>System Snapshot</h2>
                    <p class="section-copy">Each card opens the dedicated page for that admin area.</p>
                </div>
            </div>

            <div class="grid-3">
                <a href="{{ route('admin.programs.index') }}" class="card stat-card clickable-card">
                    <div class="eyebrow">Programs</div>
                    <p class="metric">{{ $programCount }}</p>
                    <p class="metric-note">Official academic programs and organization labels.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="{{ route('admin.semesters.index') }}" class="card stat-card clickable-card">
                    <div class="eyebrow">Semesters</div>
                    <p class="metric">{{ $semesterCount }}</p>
                    <p class="metric-note">Current and past clearance periods.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="{{ route('admin.students.index') }}" class="card stat-card clickable-card">
                    <div class="eyebrow">Students</div>
                    <p class="metric">{{ $studentCount }}</p>
                    <p class="metric-note">Student accounts and roster records.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="{{ route('admin.office-accounts.index') }}" class="card stat-card clickable-card">
                    <div class="eyebrow">Office Accounts</div>
                    <p class="metric">{{ $officeAccountCount }}</p>
                    <p class="metric-note">Staff accounts used for non-student designations.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="{{ route('admin.clearance-history.index') }}" class="card stat-card clickable-card">
                    <div class="eyebrow">Clearance History</div>
                    <p class="metric">{{ $completedClearanceCount }}</p>
                    <p class="metric-note">Completed records available for review and export.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="{{ route('admin.routing.index') }}" class="card stat-card clickable-card">
                    <div class="eyebrow">Routing</div>
                    <p class="metric">{{ $designationCount }}</p>
                    <p class="metric-note">Active designations and assignment coverage.</p>
                    <span class="manage-pill">Open</span>
                </a>
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
    </style>
    @endpush
@endsection
