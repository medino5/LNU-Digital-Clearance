@extends('layouts.portal', ['title' => 'Super Admin Dashboard'])

@section('page')
    <div class="topbar">
        <div>
            <h1>SUPER ADMIN DASHBOARD</h1>
            <p>Manage programs, semesters, accounts, and completed clearance history.</p>
        </div>
        <div class="toolbar">
            <span>{{ auth()->user()->name }}</span>
            <a class="button topbar-action" href="{{ route('office.login') }}">Switch to Office Portal</a>
            <form method="POST" action="{{ route('portal.logout') }}" class="topbar-form">
                @csrf
                <button type="submit" class="topbar-action">Log Out / Switch Account</button>
            </form>
        </div>
    </div>

    <div class="content stack">
        {{-- Existing: feedback callouts --}}
        @if(session('success'))
            <div class="callout success">{{ session('success') }}</div>
        @endif

        @if(session('info'))
            <div class="callout success">{{ session('info') }}</div>
        @endif

        @if(session('error'))
            <div class="callout error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="callout error">{{ $errors->first() }}</div>
        @endif

        {{-- New: Overview and quick management section --}}
        <section id="overview" class="dashboard-section">
            <div class="section-heading">
                <div>
                    <h2>DASHBOARD SUMMARY AND QUICK ACCESS</h2>
                    <p class="section-copy">Start here to review the current setup and jump directly to the area you need to manage.</p>
                </div>
            </div>

            {{-- Updated: clickable overview cards to reduce redundancy and improve navigation --}}
            <div class="grid-3">
                <a href="#academic-configuration" class="card stat-card clickable-card">
                    <div class="eyebrow">Programs</div>
                    <p class="metric">{{ $programs->count() }}</p>
                    <p class="metric-note">Available in student and office routing forms.</p>
                    <span class="manage-pill">Manage</span>
                </a>

                <a href="#academic-configuration" class="card stat-card clickable-card">
                    <div class="eyebrow">Semesters</div>
                    <p class="metric">{{ $semesters->count() }}</p>
                    <p class="metric-note">Active and archived clearance periods managed by admin.</p>
                    <span class="manage-pill">Manage</span>
                </a>

                <a href="#accounts-records" class="card stat-card clickable-card">
                    <div class="eyebrow">Students</div>
                    <p class="metric">{{ $students->count() }}</p>
                    <p class="metric-note">Admin-provisioned student accounts in the active roster.</p>
                    <span class="manage-pill">Manage</span>
                </a>

                <a href="#accounts-records" class="card stat-card clickable-card">
                    <div class="eyebrow">Office Accounts</div>
                    <p class="metric">{{ $officeAccounts->count() }}</p>
                    <p class="metric-note">Position-based accounts used for routing and approvals.</p>
                    <span class="manage-pill">Manage</span>
                </a>

                <a href="#history-records" class="card stat-card clickable-card">
                    <div class="eyebrow">Clearance History</div>
                    <p class="metric">{{ $history->flatten(1)->count() }}</p>
                    <p class="metric-note">Completed clearance records currently visible in history.</p>
                    <span class="manage-pill">Manage</span>
                </a>

                <a href="#academic-configuration" class="card stat-card clickable-card">
                    <div class="eyebrow">Academic Configuration</div>
                    <p class="metric">{{ $programs->count() + $semesters->count() }}</p>
                    <p class="metric-note">Combined setup entries for program and semester management.</p>
                    <span class="manage-pill">Manage</span>
                </a>
            </div>
        </section>

        {{-- New: Academic configuration section --}}
        <section id="academic-configuration" class="dashboard-section">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Academic Configuration</div>
                    <h2>PROGRAMS AND SEMESTERS</h2>
                </div>
            </div>

            <div class="grid-2">
                <div class="section-stack">
                    <div class="card">
                        <div class="eyebrow">Create Program</div>
                        <h3>Add future-proof program metadata</h3>
                        <form method="POST" action="{{ route('admin.programs.store') }}">
                            @csrf
                            <div class="field-grid">
                                <label>
                                    Program Code
                                    <input type="text" name="code" placeholder="BSIT" required>
                                </label>
                                <label>
                                    Organization Name
                                    <input type="text" name="org_name" placeholder="DIGITS" required>
                                </label>
                            </div>
                            <label>
                                Program Name
                                <input type="text" name="name" placeholder="Bachelor of Science in Information Technology" required>
                            </label>
                            <button type="submit">Save Program</button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Program Catalog</div>
                        <h3>Current program catalog</h3>
                        <div class="list">
                            @foreach($programs as $program)
                                <details class="record">
                                    <summary>{{ $program->code }} - {{ $program->name }}</summary>
                                    <div class="divider"></div>
                                    <form method="POST" action="{{ route('admin.programs.update', $program) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="field-grid">
                                            <label>
                                                Program Code
                                                <input type="text" name="code" value="{{ $program->code }}" required>
                                            </label>
                                            <label>
                                                Organization Name
                                                <input type="text" name="org_name" value="{{ $program->org_name }}" required>
                                            </label>
                                        </div>
                                        <label>
                                            Program Name
                                            <input type="text" name="name" value="{{ $program->name }}" required>
                                        </label>
                                        <button type="submit">Update Program</button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="section-stack">
                    <div class="card">
                        <div class="eyebrow">Create Semester</div>
                        <h3>Manage the active clearance period</h3>
                        <form method="POST" action="{{ route('admin.semesters.store') }}">
                            @csrf
                            <label>
                                Semester Label
                                <input type="text" name="label" placeholder="2nd Semester 2024-2025" required>
                            </label>
                            <label class="inline-check">
                                <input type="checkbox" name="is_active" value="1">
                                Set as the active semester
                            </label>
                            <button type="submit">Save Semester</button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Semester List</div>
                        <h3>Active and archived clearance windows</h3>
                        <div class="list">
                            @foreach($semesters as $semester)
                                <details class="record">
                                    <summary>
                                        {{ $semester->label }}
                                        @if($semester->is_active)
                                            <span class="badge active" style="margin-left: 10px;">Active</span>
                                        @endif
                                    </summary>
                                    <div class="divider"></div>
                                    <form method="POST" action="{{ route('admin.semesters.update', $semester) }}">
                                        @csrf
                                        @method('PUT')
                                        <label>
                                            Semester Label
                                            <input type="text" name="label" value="{{ $semester->label }}" required>
                                        </label>
                                        <label class="inline-check">
                                            <input type="checkbox" name="is_active" value="1" style="width:auto;" {{ $semester->is_active ? 'checked' : '' }}>
                                            Keep this semester active
                                        </label>
                                        <button type="submit">Update Semester</button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- New: Accounts and records section --}}
        <section id="accounts-records" class="dashboard-section">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Accounts and Records</div>
                    <h2>STUDENTS, OFFICE ACCOUNTS, AND CLEARANCE RECCORDS</h2>
                </div>
            </div>

            <div class="grid-2">
                <div class="section-stack">
                    <div class="card">
                        <div class="eyebrow">Create Student</div>
                        <h3>Provision mobile login credentials</h3>
                        <form method="POST" action="{{ route('admin.students.store') }}">
                            @csrf
                            <div class="field-grid">
                                <label>
                                    Student ID
                                    <input type="text" name="student_id_number" required>
                                </label>
                                <label>
                                    Full Name
                                    <input type="text" name="name" required>
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>
                                    Program
                                    <select name="program_id" required>
                                        <option value="">Select program</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}">{{ $program->code }} - {{ $program->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    Year Level
                                    <select name="year_level" required>
                                        @foreach($yearLevels as $yearLevel)
                                            <option value="{{ $yearLevel }}">{{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <label>
                                Password
                                <input type="password" name="password" required>
                            </label>
                            <button type="submit">Create Student</button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Existing Students</div>
                        <h3>Student account records</h3>
                        <div class="list scrollable-list">
                            @foreach($students as $student)
                                <details class="record">
                                    <summary>{{ $student->student_id_number }} - {{ $student->user->name }}</summary>
                                    <p class="mini">{{ $student->program->code }} | {{ $student->yearLevelLabel() }}</p>
                                    <div class="divider"></div>
                                    <form method="POST" action="{{ route('admin.students.update', $student) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="field-grid">
                                            <label>
                                                Student ID
                                                <input type="text" name="student_id_number" value="{{ $student->student_id_number }}" required>
                                            </label>
                                            <label>
                                                Full Name
                                                <input type="text" name="name" value="{{ $student->user->name }}" required>
                                            </label>
                                        </div>
                                        <div class="field-grid">
                                            <label>
                                                Program
                                                <select name="program_id" required>
                                                    @foreach($programs as $program)
                                                        <option value="{{ $program->id }}" {{ $student->program_id === $program->id ? 'selected' : '' }}>
                                                            {{ $program->code }} - {{ $program->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>
                                                Year Level
                                                <select name="year_level" required>
                                                    @foreach($yearLevels as $yearLevel)
                                                        <option value="{{ $yearLevel }}" {{ $student->year_level === $yearLevel ? 'selected' : '' }}>
                                                            {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        </div>
                                        <label>
                                            Reset Password
                                            <input type="password" name="password" placeholder="Leave blank to keep the current password">
                                        </label>
                                        <button type="submit">Update Student</button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="section-stack">
                    <div class="card">
                        <div class="eyebrow">Create Office Account</div>
                        <h3>Set up route targets for approvals</h3>
                        <form method="POST" action="{{ route('admin.office-accounts.store') }}">
                            @csrf
                            <div class="field-grid">
                                <label>
                                    Display Name
                                    <input type="text" name="display_name" required>
                                </label>
                                <label>
                                    Username
                                    <input type="text" name="username" required>
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>
                                    Office Type
                                    <select name="office_type" required>
                                        <option value="">Select type</option>
                                        @foreach($officeTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    Password
                                    <input type="password" name="password" required>
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>
                                    Program Scope
                                    <select name="program_id">
                                        <option value="">No program scope</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}">{{ $program->code }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>
                                    Year Level Scope
                                    <select name="year_level">
                                        <option value="">No year level scope</option>
                                        @foreach($yearLevels as $yearLevel)
                                            <option value="{{ $yearLevel }}">{{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <button type="submit">Create Office Account</button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Office List</div>
                        <h3>Routing targets and position logins</h3>
                        <div class="list scrollable-list">
                            @foreach($officeAccounts as $officeAccount)
                                <details class="record">
                                    <summary>{{ $officeAccount->display_name }}</summary>
                                    <p class="mini">
                                        {{ $officeAccount->officeTypeLabel() }}
                                        @if($officeAccount->program)
                                            | Program: {{ $officeAccount->program->code }}
                                        @endif
                                        @if($officeAccount->year_level)
                                            | Year: {{ $officeAccount->year_level }}
                                        @endif
                                    </p>
                                    <div class="divider"></div>
                                    <form method="POST" action="{{ route('admin.office-accounts.update', $officeAccount) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="field-grid">
                                            <label>
                                                Display Name
                                                <input type="text" name="display_name" value="{{ $officeAccount->display_name }}" required>
                                            </label>
                                            <label>
                                                Username
                                                <input type="text" name="username" value="{{ $officeAccount->user->username }}" required>
                                            </label>
                                        </div>
                                        <div class="field-grid">
                                            <label>
                                                Office Type
                                                <select name="office_type" required>
                                                    @foreach($officeTypeOptions as $value => $label)
                                                        <option value="{{ $value }}" {{ $officeAccount->office_type === $value ? 'selected' : '' }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>
                                                Reset Password
                                                <input type="password" name="password" placeholder="Leave blank to keep the current password">
                                            </label>
                                        </div>
                                        <div class="field-grid">
                                            <label>
                                                Program Scope
                                                <select name="program_id">
                                                    <option value="">No program scope</option>
                                                    @foreach($programs as $program)
                                                        <option value="{{ $program->id }}" {{ $officeAccount->program_id === $program->id ? 'selected' : '' }}>
                                                            {{ $program->code }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>
                                                Year Level Scope
                                                <select name="year_level">
                                                    <option value="">No year level scope</option>
                                                    @foreach($yearLevels as $yearLevel)
                                                        <option value="{{ $yearLevel }}" {{ $officeAccount->year_level === $yearLevel ? 'selected' : '' }}>
                                                            {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </label>
                                        </div>
                                        <button type="submit">Update Office Account</button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Updated: Clearance history kept inside accounts and records section --}}
            <div id="history-records" class="card">
                <div class="section-subheader">
                    <div>
                        <div class="eyebrow">Clearance History</div>
                        <h3>Completed clearance records by semester</h3>
                    </div>
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="toolbar">
                        <label class="history-filter">
                            <select name="history_semester" onchange="this.form.submit()">
                                <option value="">All semesters</option>
                                @foreach($semesters as $semester)
                                    <option value="{{ $semester->id }}" {{ $selectedSemesterId === $semester->id ? 'selected' : '' }}>
                                        {{ $semester->label }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </form>
                </div>

                @forelse($history as $semesterLabel => $records)
                    <div class="record history-record">
                        <h3 style="margin-top: 0;">{{ $semesterLabel }}</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Program</th>
                                    <th>Reference</th>
                                    <th>Completed</th>
                                    <th>Signed Offices</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $clearance)
                                    <tr>
                                        <td>
                                            <strong>{{ $clearance->student_id_number }}</strong><br>
                                            {{ $clearance->student_name }}<br>
                                            <span class="mini">Year {{ $clearance->year_level }}</span>
                                        </td>
                                        <td>{{ $clearance->program_code }} - {{ $clearance->program_name }}</td>
                                        <td>{{ $clearance->reference_number }}</td>
                                        <td>{{ optional($clearance->completed_at)->format('M d, Y h:i A') }}</td>
                                        <td>
                                            @foreach($clearance->steps as $step)
                                                <div class="mini">
                                                    {{ $step->office_label }}
                                                    @if($step->signed_at)
                                                        - {{ $step->signed_at->format('M d, Y h:i A') }}
                                                    @endif
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    <div class="record">
                        <p class="muted" style="margin: 0;">No completed clearances found for the selected semester filter.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
@endsection