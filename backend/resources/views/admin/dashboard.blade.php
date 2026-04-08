@extends('layouts.portal', ['title' => 'Super Admin Dashboard'])

@section('page')
    @php
        $validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all());
        $activeFormKey = old('_form_key');
    @endphp

    <div class="topbar">
        <div>
            <h1>SUPER ADMIN DASHBOARD</h1>
            <p>Manage academic setup, account records, routing assignments, and completed clearance reports.</p>
        </div>
        <div class="toolbar">
            <span>{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('portal.logout') }}" class="topbar-form">
                @csrf
                <button type="submit" class="topbar-action">Log Out</button>
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

        @if($validationErrors->isNotEmpty())
            <div class="callout error">{{ $validationErrors->first() }}</div>
        @endif

        <section class="dashboard-intro-shell">
            <div class="card dashboard-quick-actions">
                <div>
                    <div class="eyebrow">Most Used</div>
                    <h2>Common admin actions</h2>
                    <p class="section-copy">Jump straight to the tasks super admins use most during setup and reporting.</p>
                </div>

                <div class="quick-action-grid">
                    <a href="#student-create-card" class="quick-action-card">
                        <span class="quick-action-label">Create Student</span>
                        <span class="quick-action-copy">Add a new student account and clearance profile.</span>
                    </a>

                    <a href="#office-account-create-card" class="quick-action-card">
                        <span class="quick-action-label">Create Office Account</span>
                        <span class="quick-action-copy">Add an office holder account for admin assignment work.</span>
                    </a>

                    <a href="#routing-configuration" class="quick-action-card">
                        <span class="quick-action-label">Assign Holders</span>
                        <span class="quick-action-copy">Review eligible users and update designation holders.</span>
                    </a>

                    <a href="#history-records" class="quick-action-card quick-action-card--accent">
                        <span class="quick-action-label">Download Report</span>
                        <span class="quick-action-copy">Export completed clearances for the selected semester and academic year.</span>
                    </a>
                </div>
            </div>

            <div class="section-jump-links">
                <span class="section-jump-title">Jump to</span>
                <a href="#overview" class="section-jump-link">Overview</a>
                <a href="#academic-configuration" class="section-jump-link">Programs & Semesters</a>
                <a href="#routing-configuration" class="section-jump-link">Routing</a>
                <a href="#accounts-records" class="section-jump-link">Accounts</a>
                <a href="#history-records" class="section-jump-link">Reports</a>
            </div>
        </section>

        <section id="overview" class="dashboard-section">
            <div class="section-heading">
                <div>
                    <h2>SYSTEM SNAPSHOT</h2>
                    <p class="section-copy">Section counts and shortcuts for the main admin areas.</p>
                </div>
            </div>

            <div class="grid-3">
                <a href="#academic-configuration" class="card stat-card clickable-card">
                    <div class="eyebrow">Programs</div>
                    <p class="metric">{{ $programs->count() }}</p>
                    <p class="metric-note">Used in student and office setup.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="#academic-configuration" class="card stat-card clickable-card">
                    <div class="eyebrow">Semesters</div>
                    <p class="metric">{{ $semesters->count() }}</p>
                    <p class="metric-note">Current and past clearance periods.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="#accounts-records" class="card stat-card clickable-card">
                    <div class="eyebrow">Students</div>
                    <p class="metric">{{ $students->count() }}</p>
                    <p class="metric-note">Student accounts in the roster.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="#accounts-records" class="card stat-card clickable-card">
                    <div class="eyebrow">Office Accounts</div>
                    <p class="metric">{{ $officeAccounts->count() }}</p>
                    <p class="metric-note">Office holders and their titles.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="#history-records" class="card stat-card clickable-card">
                    <div class="eyebrow">Clearance History</div>
                    <p class="metric">{{ $history->flatten(1)->count() }}</p>
                    <p class="metric-note">Completed records in history.</p>
                    <span class="manage-pill">Open</span>
                </a>

                <a href="#routing-configuration" class="card stat-card clickable-card"> 
                    <div class="eyebrow">Routing Configuration</div>
                    <p class="metric">{{ $designations->count() }}</p>
                    <p class="metric-note">Choose who handles each designation.</p>
                    <span class="manage-pill">Open</span>
                </a>

            </div>
        </section>

        <section id="academic-configuration" class="dashboard-section">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Setup</div>
                    <h2>PROGRAMS AND SEMESTERS</h2>
                </div>
            </div>

            <div class="grid-2">
                <div class="section-stack">
                    <div class="card">
                        <div class="eyebrow">Create Program</div>
                        <h3>Add a program</h3>
                        <p class="section-copy compact-copy">Save the official code, name, and organization label used across routing and records.</p>
                        @php($programCreateFormKey = 'program-create')
                        <form method="POST" action="{{ route('admin.programs.store') }}">
                            @csrf
                            <input type="hidden" name="_form_key" value="{{ $programCreateFormKey }}">
                            <div class="field-grid">
                                <label>
                                    Program Code
                                    <input type="text" name="code" placeholder="BSIT" value="{{ $activeFormKey === $programCreateFormKey ? old('code') : '' }}" required>
                                    <span class="mini">Letters, numbers, and hyphens only. Saved in uppercase.</span>
                                    @if($activeFormKey === $programCreateFormKey)
                                        <x-field-error field="code" bag="programCreate" />
                                    @endif
                                </label>
                                <label>
                                    Organization Name
                                    <input type="text" name="org_name" placeholder="DIGITS" value="{{ $activeFormKey === $programCreateFormKey ? old('org_name') : '' }}" required>
                                    @if($activeFormKey === $programCreateFormKey)
                                        <x-field-error field="org_name" bag="programCreate" />
                                    @endif
                                </label>
                            </div>
                            <label>
                                Program Name
                                <input type="text" name="name" placeholder="Bachelor of Science in Information Technology" value="{{ $activeFormKey === $programCreateFormKey ? old('name') : '' }}" required>
                                @if($activeFormKey === $programCreateFormKey)
                                    <x-field-error field="name" bag="programCreate" />
                                @endif
                            </label>
                            <button 
                                type="submit"
                                data-loading-button
                                data-loading-text="Saving Program..."
                            >
                                Save Program
                            </button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Programs</div>
                        <h3>Current programs</h3>
                        <div class="list">
                            @foreach($programs as $program)
                                @php($programUpdateFormKey = 'program-update-' . $program->id)
                                <details class="record" {{ $activeFormKey === $programUpdateFormKey ? 'open' : '' }}>
                                    <summary>{{ $program->code }} - {{ $program->name }}</summary>
                                    <div class="divider"></div>
                                    <form method="POST" action="{{ route('admin.programs.update', $program) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_form_key" value="{{ $programUpdateFormKey }}">
                                        <div class="field-grid">
                                            <label>
                                                Program Code
                                                <input type="text" name="code" value="{{ $activeFormKey === $programUpdateFormKey ? old('code', $program->code) : $program->code }}" required>
                                                <span class="mini">Letters, numbers, and hyphens only. Saved in uppercase.</span>
                                                @if($activeFormKey === $programUpdateFormKey)
                                                    <x-field-error field="code" bag="programUpdate" />
                                                @endif
                                            </label>
                                            <label>
                                                Organization Name
                                                <input type="text" name="org_name" value="{{ $activeFormKey === $programUpdateFormKey ? old('org_name', $program->org_name) : $program->org_name }}" required>
                                                @if($activeFormKey === $programUpdateFormKey)
                                                    <x-field-error field="org_name" bag="programUpdate" />
                                                @endif
                                            </label>
                                        </div>
                                        <label>
                                            Program Name
                                            <input type="text" name="name" value="{{ $activeFormKey === $programUpdateFormKey ? old('name', $program->name) : $program->name }}" required>
                                            @if($activeFormKey === $programUpdateFormKey)
                                                <x-field-error field="name" bag="programUpdate" />
                                            @endif
                                        </label>
                                        <button 
                                            type="submit"
                                            data-loading-button
                                            data-loading-text="Updating Program..."
                                        >
                                            Update Program
                                        </button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="section-stack">
                    <div class="card" id="semester-create-card">
                        <div class="eyebrow">Create Semester</div>
                        <h3>Add or change the current semester</h3>
                        <p class="section-copy compact-copy">Keep the active semester and academic year ready before students initiate clearances.</p>
                        @php($semesterCreateFormKey = 'semester-create')
                        <form method="POST" action="{{ route('admin.semesters.store') }}">
                            @csrf
                            <input type="hidden" name="_form_key" value="{{ $semesterCreateFormKey }}">
                            <label>
                                Semester Label
                                <input type="text" name="label" placeholder="2nd Semester 2024-2025" value="{{ $activeFormKey === $semesterCreateFormKey ? old('label') : '' }}" required>
                                @if($activeFormKey === $semesterCreateFormKey)
                                    <x-field-error field="label" bag="semesterCreate" />
                                @endif
                            </label>
                            <label>
                                Academic Year
                                <input type="text" name="academic_year" placeholder="2024-2025" value="{{ $activeFormKey === $semesterCreateFormKey ? old('academic_year') : '' }}" required>
                                @if($activeFormKey === $semesterCreateFormKey)
                                    <x-field-error field="academic_year" bag="semesterCreate" />
                                @endif
                            </label>
                            <label class="inline-check">
                                <input type="checkbox" name="is_active" value="1" {{ $activeFormKey === $semesterCreateFormKey && old('is_active') ? 'checked' : '' }}>
                                Set as the active semester
                            </label>
                            <button 
                                type="submit"
                                data-loading-button
                                data-loading-text="Saving Semester..."
                            >
                                Save Semester
                            </button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Semester List</div>
                        <h3>Current and past semesters</h3>
                        <div class="list">
                            @foreach($semesters as $semester)
                                @php($semesterUpdateFormKey = 'semester-update-' . $semester->id)
                                <details class="record" {{ $activeFormKey === $semesterUpdateFormKey ? 'open' : '' }}>
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
                                        <input type="hidden" name="_form_key" value="{{ $semesterUpdateFormKey }}">
                                        <label>
                                            Semester Label
                                            <input type="text" name="label" value="{{ $activeFormKey === $semesterUpdateFormKey ? old('label', $semester->label) : $semester->label }}" required>
                                            @if($activeFormKey === $semesterUpdateFormKey)
                                                <x-field-error field="label" bag="semesterUpdate" />
                                            @endif
                                        </label>
                                        <label>
                                            Academic Year
                                            <input type="text" name="academic_year" value="{{ $activeFormKey === $semesterUpdateFormKey ? old('academic_year', $semester->displayAcademicYear()) : $semester->displayAcademicYear() }}" required>
                                            @if($activeFormKey === $semesterUpdateFormKey)
                                                <x-field-error field="academic_year" bag="semesterUpdate" />
                                            @endif
                                        </label>
                                        <label class="inline-check">
                                            <input
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                style="width:auto;"
                                                {{ $activeFormKey === $semesterUpdateFormKey ? (old('is_active') ? 'checked' : '') : ($semester->is_active ? 'checked' : '') }}
                                            >
                                            Keep this semester active
                                        </label>
                                        <button 
                                            type="submit"
                                            data-loading-button
                                            data-loading-text="Updating Semester..."
                                        >
                                            Update Semester
                                        </button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('admin.partials.designation-routing', [
            'designations' => $designations,
            'activeFormKey' => $activeFormKey,
        ])

        <section id="accounts-records" class="dashboard-section">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Accounts and Records</div>
                    <h2>STUDENTS, OFFICE ACCOUNTS, AND HISTORY</h2>
                </div>
            </div>

            <div class="section-stack">
                <div class="grid-2">
                    <div class="card" id="student-create-card">
                        <div class="eyebrow">Create Student</div>
                        <h3>Create a student account</h3>
                        <p class="section-copy compact-copy">Add the student identity, program, year level, and login details in one step.</p>
                        @php($studentCreateFormKey = 'student-create')
                        <form method="POST" action="{{ route('admin.students.store') }}">
                            @csrf
                            <input type="hidden" name="_form_key" value="{{ $studentCreateFormKey }}">
                            <div class="field-grid">
                                <label>
                                    First Name
                                    <input type="text" name="first_name" value="{{ $activeFormKey === $studentCreateFormKey ? old('first_name') : '' }}" required>
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="first_name" bag="studentCreate" />
                                    @endif
                                </label>
                                <label>
                                    Last Name
                                    <input type="text" name="last_name" value="{{ $activeFormKey === $studentCreateFormKey ? old('last_name') : '' }}" required>
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="last_name" bag="studentCreate" />
                                    @endif
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>
                                    Middle Initial
                                    <input type="text" name="middle_initial" value="{{ $activeFormKey === $studentCreateFormKey ? old('middle_initial') : '' }}" maxlength="1" placeholder="A">
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="middle_initial" bag="studentCreate" />
                                    @endif
                                </label>
                                <label>
                                    Extension
                                    <select name="name_extension">
                                        <option value="">No extension</option>
                                        @foreach($studentNameExtensions as $extension)
                                            <option value="{{ $extension }}" {{ $activeFormKey === $studentCreateFormKey && old('name_extension') === $extension ? 'selected' : '' }}>
                                                {{ $extension }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="name_extension" bag="studentCreate" />
                                    @endif
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>
                                    Student ID
                                    <input
                                        type="text"
                                        name="student_id_number"
                                        value="{{ $activeFormKey === $studentCreateFormKey ? old('student_id_number') : '' }}"
                                        inputmode="numeric"
                                        pattern="[0-9]{7}"
                                        maxlength="7"
                                        placeholder="2302314"
                                        data-student-id-input
                                        required
                                    >
                                    <span class="mini">Use the 7-digit format, for example 2302314.</span>
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="student_id_number" bag="studentCreate" />
                                    @endif
                                </label>
                                <label>
                                    Program
                                    <select name="program_id" required>
                                        <option value="">Select program</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}" {{ $activeFormKey === $studentCreateFormKey && (string) old('program_id') === (string) $program->id ? 'selected' : '' }}>
                                                {{ $program->code }} - {{ $program->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="program_id" bag="studentCreate" />
                                    @endif
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>
                                    Year Level
                                    <select name="year_level" required>
                                        @foreach($yearLevels as $yearLevel)
                                            <option value="{{ $yearLevel }}" {{ $activeFormKey === $studentCreateFormKey && (string) old('year_level') === (string) $yearLevel ? 'selected' : '' }}>
                                                {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="year_level" bag="studentCreate" />
                                    @endif
                                </label>
                                <label>
                                    Password
                                    <input type="password" name="password" required>
                                    @if($activeFormKey === $studentCreateFormKey)
                                        <x-field-error field="password" bag="studentCreate" />
                                    @endif
                                </label>
                            </div>
                            <button 
                                type="submit"
                                data-loading-button
                                data-loading-text="Creating Student..."
                            >
                                Create Student
                            </button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Existing Students</div>
                        <h3>Student account records</h3>
                        <div class="list scrollable-list">
                            @foreach($students as $student)
                                @php($studentUpdateFormKey = 'student-update-' . $student->id)
                                @php($studentNameParts = $student->user->studentNameParts())
                                <details class="record" {{ $activeFormKey === $studentUpdateFormKey ? 'open' : '' }}>
                                    <summary>{{ $student->student_id_number }} - {{ $student->displayName() }}</summary>
                                    <p class="mini">{{ $student->program->code }} | {{ $student->yearLevelLabel() }}</p>
                                    <div class="divider"></div>
                                    <form method="POST" action="{{ route('admin.students.update', $student) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_form_key" value="{{ $studentUpdateFormKey }}">
                                        <div class="field-grid">
                                            <label>
                                                First Name
                                                <input type="text" name="first_name" value="{{ $activeFormKey === $studentUpdateFormKey ? old('first_name', $studentNameParts['first_name']) : $studentNameParts['first_name'] }}" required>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="first_name" bag="studentUpdate" />
                                                @endif
                                            </label>
                                            <label>
                                                Last Name
                                                <input type="text" name="last_name" value="{{ $activeFormKey === $studentUpdateFormKey ? old('last_name', $studentNameParts['last_name']) : $studentNameParts['last_name'] }}" required>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="last_name" bag="studentUpdate" />
                                                @endif
                                            </label>
                                        </div>
                                        <div class="field-grid">
                                            <label>
                                                Middle Initial
                                                <input type="text" name="middle_initial" value="{{ $activeFormKey === $studentUpdateFormKey ? old('middle_initial', $studentNameParts['middle_initial']) : $studentNameParts['middle_initial'] }}" maxlength="1" placeholder="A">
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="middle_initial" bag="studentUpdate" />
                                                @endif
                                            </label>
                                            <label>
                                                Extension
                                                <select name="name_extension">
                                                    <option value="">No extension</option>
                                                    @foreach($studentNameExtensions as $extension)
                                                        <option
                                                            value="{{ $extension }}"
                                                            {{ ($activeFormKey === $studentUpdateFormKey ? old('name_extension', $studentNameParts['name_extension']) === $extension : $studentNameParts['name_extension'] === $extension) ? 'selected' : '' }}
                                                        >
                                                            {{ $extension }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="name_extension" bag="studentUpdate" />
                                                @endif
                                            </label>
                                        </div>
                                        <div class="field-grid">
                                            <label>
                                                Student ID
                                                <input
                                                    type="text"
                                                    name="student_id_number"
                                                    value="{{ $activeFormKey === $studentUpdateFormKey ? old('student_id_number', $student->student_id_number) : $student->student_id_number }}"
                                                    inputmode="numeric"
                                                    pattern="[0-9]{7}"
                                                    maxlength="7"
                                                    placeholder="2302314"
                                                    data-student-id-input
                                                    required
                                                >
                                                <span class="mini">Use the 7-digit format, for example 2302314.</span>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="student_id_number" bag="studentUpdate" />
                                                @endif
                                            </label>
                                            <label>
                                                Program
                                                <select name="program_id" required>
                                                    @foreach($programs as $program)
                                                        <option
                                                            value="{{ $program->id }}"
                                                            {{ ($activeFormKey === $studentUpdateFormKey ? (string) old('program_id', $student->program_id) === (string) $program->id : $student->program_id === $program->id) ? 'selected' : '' }}
                                                        >
                                                            {{ $program->code }} - {{ $program->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="program_id" bag="studentUpdate" />
                                                @endif
                                            </label>
                                        </div>
                                        <div class="field-grid">
                                            <label>
                                                Year Level
                                                <select name="year_level" required>
                                                    @foreach($yearLevels as $yearLevel)
                                                        <option
                                                            value="{{ $yearLevel }}"
                                                            {{ ($activeFormKey === $studentUpdateFormKey ? (string) old('year_level', $student->year_level) === (string) $yearLevel : $student->year_level === $yearLevel) ? 'selected' : '' }}
                                                        >
                                                            {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="year_level" bag="studentUpdate" />
                                                @endif
                                            </label>
                                            <label>
                                                Reset Password
                                                <input type="password" name="password" placeholder="Leave blank to keep the current password">
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="password" bag="studentUpdate" />
                                                @endif
                                            </label>
                                        </div>
                                        <button 
                                            type="submit"
                                            data-loading-button
                                            data-loading-text="Updating Student..."
                                        >
                                            Update Student
                                        </button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="section-stack">
                    <div class="card" id="office-account-create-card">
                        <div class="eyebrow">Create Office Account</div>
                        <h3>Add an office account</h3>
                        @php($officeCreateFormKey = 'office-account-create')
                        <form method="POST" action="{{ route('admin.office-accounts.store') }}" data-office-account-form>
                            @csrf
                            <input type="hidden" name="_form_key" value="{{ $officeCreateFormKey }}">
                            <p class="section-copy" style="margin-top: 0;">
                                Add a staff account for adviser, librarian, or VPSD assignment work.
                            </p>
                            <div class="field-grid">
                                <label>
                                    Officer Name
                                    <input type="text" name="display_name" value="{{ $activeFormKey === $officeCreateFormKey ? old('display_name') : '' }}" required>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="display_name" bag="officeAccountCreate" />
                                    @endif
                                </label>
                                <label>
                                    Username
                                    <input type="text" name="username" value="{{ $activeFormKey === $officeCreateFormKey ? old('username') : '' }}" required>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="username" bag="officeAccountCreate" />
                                    @endif
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>
                                    Office Type
                                    <select name="office_type" required data-office-type-select>
                                        <option value="">Select type</option>
                                        @foreach($officeAccountTypeOptions as $value => $label)
                                            <option value="{{ $value }}" {{ $activeFormKey === $officeCreateFormKey && old('office_type') === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="office_type" bag="officeAccountCreate" />
                                    @endif
                                </label>
                                <label>
                                    Password
                                    <input type="password" name="password" required>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="password" bag="officeAccountCreate" />
                                    @endif
                                </label>
                            </div>
                            <p class="mini" data-scope-note style="margin: -2px 0 8px; color: #5b6578;">
                                Choose an office type to set the staff account's usual scope. Non-student designation assignment is handled separately.
                            </p>
                            <div class="field-grid">
                                <label>
                                    Program Scope
                                    <select name="program_id" data-program-scope-select>
                                        <option value="">Select program scope</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}" {{ $activeFormKey === $officeCreateFormKey && (string) old('program_id') === (string) $program->id ? 'selected' : '' }}>
                                                {{ $program->code }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="program_id" bag="officeAccountCreate" />
                                    @endif
                                </label>
                                <label>
                                    Year Level Scope
                                    <select name="year_level" data-year-level-scope-select>
                                        <option value="">Select year level scope</option>
                                        @foreach($yearLevels as $yearLevel)
                                            <option value="{{ $yearLevel }}" {{ $activeFormKey === $officeCreateFormKey && (string) old('year_level') === (string) $yearLevel ? 'selected' : '' }}>
                                                {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                            </option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="year_level" bag="officeAccountCreate" />
                                    @endif
                                </label>
                            </div>
                            <button 
                                type="submit"
                                data-loading-button
                                data-loading-text="Creating Office Account..."
                            >
                                Create Office Account
                            </button>
                        </form>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="card" id="student-records">
                        <div class="eyebrow">Existing Students</div>
                        <h3>Student account records</h3>

                        <form method="GET" action="{{ route('admin.dashboard') }}#student-records" class="roster-filter-bar">
                            <input type="text" name="student_search" value="{{ $studentSearch }}" placeholder="Search by name or ID">

                            <select name="student_program">
                                <option value="">Program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ (string) $studentProgramId === (string) $program->id ? 'selected' : '' }}>
                                        {{ $program->code }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="student_year_level">
                                <option value="">Year Level</option>
                                @foreach($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel }}" {{ (string) $studentYearLevel === (string) $yearLevel ? 'selected' : '' }}>
                                        {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                    </option>
                                @endforeach
                            </select>

                            @if($officeSearch !== '')
                                <input type="hidden" name="office_search" value="{{ $officeSearch }}">
                            @endif
                            @if($officeProgramId !== null && $officeProgramId !== '')
                                <input type="hidden" name="office_program" value="{{ $officeProgramId }}">
                            @endif
                            @if($selectedOfficeType !== null && $selectedOfficeType !== '')
                                <input type="hidden" name="office_type" value="{{ $selectedOfficeType }}">
                            @endif
                            @if($selectedSemesterId)
                                <input type="hidden" name="history_semester" value="{{ $selectedSemesterId }}">
                            @endif
                            @if($selectedAcademicYear !== '')
                                <input type="hidden" name="history_academic_year" value="{{ $selectedAcademicYear }}">
                            @endif

                            <button type="submit" class="button">Apply</button>
                            <a href="{{ route('admin.dashboard', array_filter([
                                'office_search' => $officeSearch !== '' ? $officeSearch : null,
                                'office_program' => $officeProgramId !== null && $officeProgramId !== '' ? $officeProgramId : null,
                                'office_type' => $selectedOfficeType !== null && $selectedOfficeType !== '' ? $selectedOfficeType : null,
                                'history_semester' => $selectedSemesterId ?: null,
                                'history_academic_year' => $selectedAcademicYear !== '' ? $selectedAcademicYear : null,
                            ])) }}#student-records" class="button secondary-button">Reset</a>
                        </form>

                        @if($students->isEmpty())
                            <div class="record">
                                <p class="muted" style="margin: 0;">No matching records found.</p>
                            </div>
                        @else
                            <div class="list scrollable-list">
                                @foreach($students as $student)
                                    @php($studentUpdateFormKey = 'student-update-' . $student->id)
                                    @php($studentNameParts = $student->user->studentNameParts())
                                    <details class="record" {{ $activeFormKey === $studentUpdateFormKey ? 'open' : '' }}>
                                        <summary>{{ $student->student_id_number }} - {{ $student->displayName() }}</summary>
                                        <p class="mini">{{ $student->program->code }} | {{ $student->yearLevelLabel() }}</p>
                                        <div class="divider"></div>
                                        <form method="POST" action="{{ route('admin.students.update', $student) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="_form_key" value="{{ $studentUpdateFormKey }}">
                                            <div class="field-grid">
                                                <label>
                                                    First Name
                                                    <input type="text" name="first_name" value="{{ $activeFormKey === $studentUpdateFormKey ? old('first_name', $studentNameParts['first_name']) : $studentNameParts['first_name'] }}" required>
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="first_name" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                                <label>
                                                    Last Name
                                                    <input type="text" name="last_name" value="{{ $activeFormKey === $studentUpdateFormKey ? old('last_name', $studentNameParts['last_name']) : $studentNameParts['last_name'] }}" required>
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="last_name" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="field-grid">
                                                <label>
                                                    Middle Initial
                                                    <input type="text" name="middle_initial" value="{{ $activeFormKey === $studentUpdateFormKey ? old('middle_initial', $studentNameParts['middle_initial']) : $studentNameParts['middle_initial'] }}" maxlength="1" placeholder="A">
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="middle_initial" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                                <label>
                                                    Extension
                                                    <select name="name_extension">
                                                        <option value="">No extension</option>
                                                        @foreach($studentNameExtensions as $extension)
                                                            <option
                                                                value="{{ $extension }}"
                                                                {{ ($activeFormKey === $studentUpdateFormKey ? old('name_extension', $studentNameParts['name_extension']) === $extension : $studentNameParts['name_extension'] === $extension) ? 'selected' : '' }}
                                                            >
                                                                {{ $extension }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="name_extension" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="field-grid">
                                                <label>
                                                    Student ID
                                                    <input
                                                        type="text"
                                                        name="student_id_number"
                                                        value="{{ $activeFormKey === $studentUpdateFormKey ? old('student_id_number', $student->student_id_number) : $student->student_id_number }}"
                                                        inputmode="numeric"
                                                        pattern="[0-9]{7}"
                                                        maxlength="7"
                                                        placeholder="2302314"
                                                        data-student-id-input
                                                        required
                                                    >
                                                    <span class="mini">Use the 7-digit format, for example 2302314.</span>
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="student_id_number" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                                <label>
                                                    Program
                                                    <select name="program_id" required>
                                                        @foreach($programs as $program)
                                                            <option
                                                                value="{{ $program->id }}"
                                                                {{ ($activeFormKey === $studentUpdateFormKey ? (string) old('program_id', $student->program_id) === (string) $program->id : $student->program_id === $program->id) ? 'selected' : '' }}
                                                            >
                                                                {{ $program->code }} - {{ $program->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="program_id" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="field-grid">
                                                <label>
                                                    Year Level
                                                    <select name="year_level" required>
                                                        @foreach($yearLevels as $yearLevel)
                                                            <option
                                                                value="{{ $yearLevel }}"
                                                                {{ ($activeFormKey === $studentUpdateFormKey ? (string) old('year_level', $student->year_level) === (string) $yearLevel : $student->year_level === $yearLevel) ? 'selected' : '' }}
                                                            >
                                                                {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="year_level" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                                <label>
                                                    Reset Password
                                                    <input type="password" name="password" placeholder="Leave blank to keep the current password">
                                                    @if($activeFormKey === $studentUpdateFormKey)
                                                        <x-field-error field="password" bag="studentUpdate" />
                                                    @endif
                                                </label>
                                            </div>
                                            <button type="submit">Update Student</button>
                                        </form>
                                    </details>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="card" id="office-records">
                        <div class="eyebrow">Office List</div>
                        <h3>Staff office accounts</h3>

                        <form method="GET" action="{{ route('admin.dashboard') }}#office-records" class="roster-filter-bar">
                            <input type="text" name="office_search" value="{{ $officeSearch }}" placeholder="Search by name, username, or scope">

                            <select name="office_program">
                                <option value="">Program Scope</option>
                                <option value="university" {{ $officeProgramId === 'university' ? 'selected' : '' }}>University-wide</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ (string) $officeProgramId === (string) $program->id ? 'selected' : '' }}>
                                        {{ $program->code }}
                                    </option>
                                @endforeach
                            </select>

                            <select name="office_type">
                                <option value="">Office Type</option>
                                @foreach($officeTypeOptions as $value => $label)
                                    <option value="{{ $value }}" {{ $selectedOfficeType === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>

                            @if($studentSearch !== '')
                                <input type="hidden" name="student_search" value="{{ $studentSearch }}">
                            @endif
                            @if($studentProgramId !== null && $studentProgramId !== '')
                                <input type="hidden" name="student_program" value="{{ $studentProgramId }}">
                            @endif
                            @if($studentYearLevel !== null && $studentYearLevel !== '')
                                <input type="hidden" name="student_year_level" value="{{ $studentYearLevel }}">
                            @endif
                            @if($selectedSemesterId)
                                <input type="hidden" name="history_semester" value="{{ $selectedSemesterId }}">
                            @endif
                            @if($selectedAcademicYear !== '')
                                <input type="hidden" name="history_academic_year" value="{{ $selectedAcademicYear }}">
                            @endif

                            <button type="submit" class="button">Apply</button>
                            <a href="{{ route('admin.dashboard', array_filter([
                                'student_search' => $studentSearch !== '' ? $studentSearch : null,
                                'student_program' => $studentProgramId !== null && $studentProgramId !== '' ? $studentProgramId : null,
                                'student_year_level' => $studentYearLevel !== null && $studentYearLevel !== '' ? $studentYearLevel : null,
                                'history_semester' => $selectedSemesterId ?: null,
                                'history_academic_year' => $selectedAcademicYear !== '' ? $selectedAcademicYear : null,
                            ])) }}#office-records" class="button secondary-button">Reset</a>
                        </form>

                        @if($officeAccounts->isEmpty())
                            <div class="record">
                                <p class="muted" style="margin: 0;">No matching records found.</p>
                            </div>
                        @else
                            <div class="list scrollable-list">
                                @foreach($officeAccounts as $officeAccount)
                                    @php($officeUpdateFormKey = 'office-account-update-' . $officeAccount->id)
                                    <details class="record" {{ $activeFormKey === $officeUpdateFormKey ? 'open' : '' }}>
                                        <summary>{{ $officeAccount->display_name }}</summary>
                                        <p class="mini">
                                            {{ $officeAccount->officeTypeLabel() }}
                                            | {{ $officeAccount->scopeSummaryLabel() }}
                                            | Username: {{ $officeAccount->user->username }}
                                        </p>
                                        <div class="divider"></div>
                                        <form method="POST" action="{{ route('admin.office-accounts.update', $officeAccount) }}" data-office-account-form>
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="_form_key" value="{{ $officeUpdateFormKey }}">
                                            <div class="field-grid">
                                                <div>
                                                    <div class="eyebrow">Account Type</div>
                                                    <p style="margin-top: 6px;">{{ $officeAccount->officeTypeLabel() }}</p>
                                                </div>

                                                <div>
                                                    <div class="eyebrow">Scope</div>
                                                    <p style="margin-top: 6px;">{{ $officeAccount->scopeSummaryLabel() }}</p>
                                                </div>
                                            </div>
                                            <div class="divider"></div>
                                            <div class="field-grid">
                                                <label>
                                                    Officer Name
                                                    <input type="text" name="display_name" value="{{ $activeFormKey === $officeUpdateFormKey ? old('display_name', $officeAccount->display_name) : $officeAccount->display_name }}" required>
                                                    @if($activeFormKey === $officeUpdateFormKey)
                                                        <x-field-error field="display_name" bag="officeAccountUpdate" />
                                                    @endif
                                                </label>
                                                <label>
                                                    Username
                                                    <input type="text" name="username" value="{{ $activeFormKey === $officeUpdateFormKey ? old('username', $officeAccount->user->username) : $officeAccount->user->username }}" required>
                                                    @if($activeFormKey === $officeUpdateFormKey)
                                                        <x-field-error field="username" bag="officeAccountUpdate" />
                                                    @endif
                                                </label>
                                            </div>
                                            <div class="field-grid">
                                                <label>
                                                    Office Type
                                                    <select name="office_type" required data-office-type-select>
                                                        @foreach($officeAccountEditTypeOptions[$officeAccount->id] ?? $officeAccountTypeOptions as $value => $label)
                                                            <option
                                                                value="{{ $value }}"
                                                                {{ ($activeFormKey === $officeUpdateFormKey ? old('office_type', $officeAccount->office_type) === $value : $officeAccount->office_type === $value) ? 'selected' : '' }}
                                                            >
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($activeFormKey === $officeUpdateFormKey)
                                                        <x-field-error field="office_type" bag="officeAccountUpdate" />
                                                    @endif
                                                </label>
                                                <label>
                                                    Reset Password
                                                    <input type="password" name="password" placeholder="Leave blank to keep the current password">
                                                    @if($activeFormKey === $officeUpdateFormKey)
                                                        <x-field-error field="password" bag="officeAccountUpdate" />
                                                    @endif
                                                </label>
                                            </div>
                                            <p class="mini" data-scope-note style="margin: -2px 0 8px; color: #5b6578;">
                                                {{ $officeTypeScopeMetadata[$officeAccount->office_type]['note'] ?? 'Choose an office type to set the staff account\'s usual scope.' }}
                                            </p>
                                            <div class="field-grid">
                                                <label>
                                                    Program Scope
                                                    <select name="program_id" data-program-scope-select>
                                                        <option value="">Select program scope</option>
                                                        @foreach($programs as $program)
                                                            <option
                                                                value="{{ $program->id }}"
                                                                {{ ($activeFormKey === $officeUpdateFormKey ? (string) old('program_id', $officeAccount->program_id) === (string) $program->id : $officeAccount->program_id === $program->id) ? 'selected' : '' }}
                                                            >
                                                                {{ $program->code }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($activeFormKey === $officeUpdateFormKey)
                                                        <x-field-error field="program_id" bag="officeAccountUpdate" />
                                                    @endif
                                                </label>
                                                <label>
                                                    Year Level Scope
                                                    <select name="year_level" data-year-level-scope-select>
                                                        <option value="">Select year level scope</option>
                                                        @foreach($yearLevels as $yearLevel)
                                                            <option
                                                                value="{{ $yearLevel }}"
                                                                {{ ($activeFormKey === $officeUpdateFormKey ? (string) old('year_level', $officeAccount->year_level) === (string) $yearLevel : $officeAccount->year_level === $yearLevel) ? 'selected' : '' }}
                                                            >
                                                                {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @if($activeFormKey === $officeUpdateFormKey)
                                                        <x-field-error field="year_level" bag="officeAccountUpdate" />
                                                    @endif
                                                </label>
                                            </div>
                                            <button
                                                type="submit"
                                                data-loading-button
                                                data-loading-text="Updating Office Account..."
                                            >
                                                Update Office Account
                                            </button>
                                        </form>
                                    </details>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Updated: Clearance history kept inside accounts and records section --}}
            <div id="history-records" class="card history-panel">
                <div class="section-subheader">
                    <div>
                        <div class="eyebrow">Clearance History</div>
                        <h3>Completed clearance records by semester and academic year</h3>
                        <p class="section-copy compact-copy">Review finished clearances and export a workbook grouped by program.</p>
                    </div>
                    <div class="toolbar" style="gap: 12px; align-items: flex-end;">
                        <form method="GET" action="{{ route('admin.dashboard') }}" class="toolbar" style="gap: 12px; align-items: flex-end;">
                            <label class="history-filter">
                                <span class="mini">Academic Year</span>
                                <select name="history_academic_year" onchange="this.form.submit()">
                                    <option value="">All academic years</option>
                                    @foreach($academicYears as $academicYear)
                                        <option value="{{ $academicYear }}" {{ $selectedAcademicYear === $academicYear ? 'selected' : '' }}>
                                            {{ $academicYear }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="history-filter">
                                <span class="mini">Semester</span>
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

                        <form method="POST" action="{{ route('admin.clearance-reports.completed.export') }}" id="history-export-form">
                            @csrf
                            <input type="hidden" name="_form_key" value="history-export">
                            <label class="history-filter">
                                <span class="mini">Download Report</span>
                                <button type="submit" class="button history-download-button">Download Excel Report</button>
                            </label>
                            <input type="hidden" name="semester_id" value="{{ old('semester_id', $selectedSemesterId) }}">
                            <input type="hidden" name="academic_year" value="{{ old('academic_year', $selectedAcademicYear) }}">
                        </form>
                    </div>
                </div>

                @if($activeFormKey === 'history-export')
                    <div class="record" style="margin-bottom: 12px;">
                        <p class="mini" style="margin: 0 0 8px;">Export validation</p>
                        <x-field-error field="semester_id" bag="historyExport" />
                        <x-field-error field="academic_year" bag="historyExport" />
                    </div>
                @endif

                <?php if ($history->isNotEmpty()): ?>
                    <?php foreach ($history as $semesterLabel => $records): ?>
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
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="record">
                        <p class="muted" style="margin: 0;">No completed clearances found for the selected semester and academic year filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

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

        .section-jump-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .section-jump-title {
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #7a6345;
        }

        .section-jump-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 14px;
            border-radius: 999px;
            text-decoration: none;
            color: #294c7a;
            background: #f5efe6;
            border: 1px solid #e3d7c7;
            font-weight: 600;
        }

        .compact-copy {
            margin-top: 0;
            margin-bottom: 14px;
            max-width: 60ch;
        }

        .history-panel .section-subheader {
            gap: 18px;
            align-items: flex-end;
        }

        .history-download-button {
            min-width: 220px;
        }

        .roster-filter-bar {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
        }

        .roster-filter-bar input,
        .roster-filter-bar select,
        .roster-filter-bar .button {
            width: 100%;
            margin: 0;
        }

        .roster-filter-bar .button {
            width: auto;
            white-space: nowrap;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        @media (max-width: 1100px) {
            .quick-action-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .roster-filter-bar {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .quick-action-grid {
                grid-template-columns: 1fr;
            }

            .section-jump-links {
                align-items: stretch;
            }

            .section-jump-link {
                width: 100%;
            }

            .roster-filter-bar {
                grid-template-columns: 1fr;
            }

            .roster-filter-bar .button {
                width: 100%;
            }
        }
    </style>
    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const normalizeStudentId = (value) => value.replace(/\D+/g, '').slice(0, 7);
            const officeTypeScopeMetadata = @json($officeTypeScopeMetadata);

            document.querySelectorAll('input[data-student-id-input]').forEach((input) => {
                input.addEventListener('beforeinput', (event) => {
                    if (event.inputType === 'insertText' && event.data && /\D/.test(event.data)) {
                        event.preventDefault();
                    }
                });

                input.addEventListener('input', () => {
                    const normalized = normalizeStudentId(input.value);

                    if (input.value !== normalized) {
                        input.value = normalized;
                    }
                });

                input.addEventListener('paste', (event) => {
                    event.preventDefault();

                    const clipboard = event.clipboardData || window.clipboardData;
                    const pastedText = clipboard ? clipboard.getData('text') : '';
                    const selectionStart = input.selectionStart ?? input.value.length;
                    const selectionEnd = input.selectionEnd ?? input.value.length;
                    const nextValue = normalizeStudentId(
                        input.value.slice(0, selectionStart) +
                        pastedText +
                        input.value.slice(selectionEnd)
                    );

                    input.value = nextValue;
                });
            });

            document.querySelectorAll('form[data-office-account-form]').forEach((form) => {
                const officeTypeSelect = form.querySelector('[data-office-type-select]');
                const programScopeSelect = form.querySelector('[data-program-scope-select]');
                const yearLevelScopeSelect = form.querySelector('[data-year-level-scope-select]');
                const scopeNote = form.querySelector('[data-scope-note]');

                if (!officeTypeSelect || !programScopeSelect || !yearLevelScopeSelect || !scopeNote) {
                    return;
                }

                const applyOfficeScopeState = () => {
                    const officeType = officeTypeSelect.value;
                    const scopeMeta = officeTypeScopeMetadata[officeType] ?? null;
                    const scopeType = scopeMeta ? scopeMeta.scope : null;
                    const requiresProgram = scopeType === 'program';
                    const requiresYearLevel = scopeType === 'year_level';

                    programScopeSelect.disabled = !requiresProgram;
                    yearLevelScopeSelect.disabled = !requiresYearLevel;

                    if (!requiresProgram) {
                        programScopeSelect.value = '';
                    }

                    if (!requiresYearLevel) {
                        yearLevelScopeSelect.value = '';
                    }

                    scopeNote.textContent = scopeMeta
                        ? scopeMeta.note
                        : 'Choose an office type to see which fields are needed.';
                };

                officeTypeSelect.addEventListener('change', applyOfficeScopeState);
                applyOfficeScopeState();
            });

            document.querySelectorAll('form').forEach((form) => {
                const submitButton = form.querySelector('[data-loading-button]');

                if (!submitButton) {
                    return;
                }

                form.addEventListener('submit', (event) => {
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
    @endpush

@endsection
