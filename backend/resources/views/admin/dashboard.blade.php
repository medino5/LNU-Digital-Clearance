@extends('layouts.portal', ['title' => 'Super Admin Dashboard'])

@section('page')
    @php
        $validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all());
        $activeFormKey = old('_form_key');
    @endphp

    <div class="topbar">
        <div>
            <h1>SUPER ADMIN DASHBOARD</h1>
            <p>Manage programs, semesters, accounts, designation assignments, and completed clearance history.</p>
        </div>
        <div class="toolbar">
            <span>{{ auth()->user()->name }}</span>
            <a class="button topbar-action" href="{{ route('portal.login') }}">Open Shared Login</a>
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

        @if($validationErrors->isNotEmpty())
            <div class="callout error">{{ $validationErrors->first() }}</div>
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

                <a href="#routing-configuration" class="card stat-card clickable-card"> 
                    <div class="eyebrow">Routing Configuration</div>
                    <p class="metric">{{ $designations->count() }}</p>
                    <p class="metric-note">Designation routing and current office-user assignment controls.</p>
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
                        @php($programCreateFormKey = 'program-create')
                        <form method="POST" action="{{ route('admin.programs.store') }}">
                            @csrf
                            <input type="hidden" name="_form_key" value="{{ $programCreateFormKey }}">
                            <div class="field-grid">
                                <label>
                                    Program Code
                                    <input type="text" name="code" placeholder="BSIT" value="{{ $activeFormKey === $programCreateFormKey ? old('code') : '' }}" required>
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
                            <button type="submit">Save Program</button>
                        </form>
                    </div>

                    <div class="card">
                        <div class="eyebrow">Program Catalog</div>
                        <h3>Current program catalog</h3>
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
                            <label class="inline-check">
                                <input type="checkbox" name="is_active" value="1" {{ $activeFormKey === $semesterCreateFormKey && old('is_active') ? 'checked' : '' }}>
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
                                        <button type="submit">Update Semester</button>
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
                                            <option value="{{ $program->id }}" {{ $activeFormKey === $studentCreateFormKey && (string) old('program_id') === (string) $program->id ? 'selected' : '' }}>{{ $program->code }} - {{ $program->name }}</option>
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
                                            <option value="{{ $yearLevel }}" {{ $activeFormKey === $studentCreateFormKey && (string) old('year_level') === (string) $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year</option>
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
                            <button type="submit">Create Student</button>
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
                        @php($officeCreateFormKey = 'office-account-create')
                        <form method="POST" action="{{ route('admin.office-accounts.store') }}">
                            @csrf
                            <input type="hidden" name="_form_key" value="{{ $officeCreateFormKey }}">
                            <div class="field-grid">
                                <label>
                                    Display Name
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
                                    <select name="office_type" required>
                                        <option value="">Select type</option>
                                        @foreach($officeTypeOptions as $value => $label)
                                            <option value="{{ $value }}" {{ $activeFormKey === $officeCreateFormKey && old('office_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
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
                            <div class="field-grid">
                                <label>
                                    Program Scope
                                    <select name="program_id">
                                        <option value="">No program scope</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}" {{ $activeFormKey === $officeCreateFormKey && (string) old('program_id') === (string) $program->id ? 'selected' : '' }}>{{ $program->code }}</option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="program_id" bag="officeAccountCreate" />
                                    @endif
                                </label>
                                <label>
                                    Year Level Scope
                                    <select name="year_level">
                                        <option value="">No year level scope</option>
                                        @foreach($yearLevels as $yearLevel)
                                            <option value="{{ $yearLevel }}" {{ $activeFormKey === $officeCreateFormKey && (string) old('year_level') === (string) $yearLevel ? 'selected' : '' }}>{{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year</option>
                                        @endforeach
                                    </select>
                                    @if($activeFormKey === $officeCreateFormKey)
                                        <x-field-error field="year_level" bag="officeAccountCreate" />
                                    @endif
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
                                @php($officeUpdateFormKey = 'office-account-update-' . $officeAccount->id)
                                <details class="record" {{ $activeFormKey === $officeUpdateFormKey ? 'open' : '' }}>
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
                                        <input type="hidden" name="_form_key" value="{{ $officeUpdateFormKey }}">
                                        <div class="field-grid">
                                            <label>
                                                Display Name
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
                                                <select name="office_type" required>
                                                    @foreach($officeTypeOptions as $value => $label)
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
                                        <div class="field-grid">
                                            <label>
                                                Program Scope
                                                <select name="program_id">
                                                    <option value="">No program scope</option>
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
                                                <select name="year_level">
                                                    <option value="">No year level scope</option>
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
                        <p class="muted" style="margin: 0;">No completed clearances found for the selected semester filter.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const normalizeStudentId = (value) => value.replace(/\D+/g, '').slice(0, 7);

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
                        input.value.slice(0, selectionStart) + pastedText + input.value.slice(selectionEnd)
                    );

                    input.value = nextValue;
                });
            });
        });
    </script>

@endsection
