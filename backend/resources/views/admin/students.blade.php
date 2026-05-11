@extends('layouts.portal', [
    'title' => 'Students',
    'subtitle' => 'Create, search, and maintain student roster records.',
])

@section('page')
    @php
        $activeFormKey = old('_form_key');
        $studentCreateFormKey = 'student-create';
        $shouldOpenCreate = $activeFormKey === $studentCreateFormKey;
    @endphp

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>STUDENTS</h1>
                <p>Add new student accounts and manage the searchable student roster.</p>
            </div>

            <button
                type="button"
                class="management-primary-action"
                data-modal-open="student-create-card"
            >
                + Add Student
            </button>
        </section>

        <section class="admin-section-card management-card" id="student-records">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Student Roster</div>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.students.index') }}" class="student-filter-bar">
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
                    <option value="">Year level</option>
                    @foreach($yearLevels as $yearLevel)
                        <option value="{{ $yearLevel }}" {{ (string) $studentYearLevel === (string) $yearLevel ? 'selected' : '' }}>
                            {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="button">Apply</button>
                <a href="{{ route('admin.students.index') }}" class="button secondary secondary-button">Reset</a>
            </form>

            @if(!$hasStudents)
                <div class="empty-state">
                    No students match the current filters.
                </div>
            @else
                <div class="mini">
                    Showing {{ $students->firstItem() }} - {{ $students->lastItem() }} of {{ $students->total() }} students
                </div>

                <div class="management-table-wrap">
                    <table class="management-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Program</th>
                                <th>Year Level</th>
                                <th class="management-action-col">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td>
                                        <strong class="student-id-pill">{{ $student->student_id_number }}</strong>
                                    </td>
                                    <td>
                                        <a
                                            href="{{ route('admin.students.show', $student) }}"
                                            class="table-main-text student-profile-link"
                                            data-student-profile-url="{{ route('admin.students.show', [$student, 'partial' => 1]) }}"
                                            data-student-profile-full-url="{{ route('admin.students.show', $student) }}"
                                        >
                                            {{ $student->displayName() }}
                                        </a>
                                    </td>
                                    <td>{{ $student->program->code }}</td>
                                    <td>{{ $student->yearLevelLabel() }}</td>
                                    <td>
                                        <div class="table-action-stack">
                                            <a
                                                href="{{ route('admin.students.show', $student) }}"
                                                class="button secondary table-action-button student-profile-link"
                                                data-student-profile-url="{{ route('admin.students.show', [$student, 'partial' => 1]) }}"
                                                data-student-profile-full-url="{{ route('admin.students.show', $student) }}"
                                            >
                                                View
                                            </a>

                                            <button
                                                type="button"
                                                class="button secondary table-action-button"
                                                data-modal-open="student-edit-{{ $student->id }}"
                                            >
                                                Edit
                                            </button>

                                            <button
                                                type="button"
                                                class="button warn table-action-button"
                                                data-modal-open="student-delete-{{ $student->id }}"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    {{ $students->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>

        {{-- CREATE MODAL --}}
        <div
            class="management-modal {{ $shouldOpenCreate ? 'is-open' : '' }}"
            id="student-create-card"
            data-modal
        >
            <div class="management-modal-panel">
                <div class="management-modal-header">
                    <div>
                        <div class="eyebrow">Create Student</div>
                        <h2>Add Student</h2>
                    </div>

                    <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.students.store') }}">
                    @csrf
                    <input type="hidden" name="_form_key" value="{{ $studentCreateFormKey }}">

                    <div class="field-grid">
                        <label>
                            First Name
                            <input type="text" name="first_name" value="{{ $shouldOpenCreate ? old('first_name') : '' }}" maxlength="60" required>
                            @if($shouldOpenCreate)
                                <x-field-error field="first_name" bag="studentCreate" />
                            @endif
                        </label>

                        <label>
                            Last Name
                            <input type="text" name="last_name" value="{{ $shouldOpenCreate ? old('last_name') : '' }}" maxlength="60" required>
                            @if($shouldOpenCreate)
                                <x-field-error field="last_name" bag="studentCreate" />
                            @endif
                        </label>
                    </div>

                    <div class="field-grid">
                        <label>
                            Middle Initial
                            <input type="text" name="middle_initial" value="{{ $shouldOpenCreate ? old('middle_initial') : '' }}" maxlength="1" placeholder="A">
                            @if($shouldOpenCreate)
                                <x-field-error field="middle_initial" bag="studentCreate" />
                            @endif
                        </label>

                        <label>
                            Extension
                            <select name="name_extension">
                                <option value="">No extension</option>
                                @foreach($studentNameExtensions as $extension)
                                    <option value="{{ $extension }}" {{ $shouldOpenCreate && old('name_extension') === $extension ? 'selected' : '' }}>
                                        {{ $extension }}
                                    </option>
                                @endforeach
                            </select>
                            @if($shouldOpenCreate)
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
                                value="{{ $shouldOpenCreate ? old('student_id_number') : '' }}"
                                inputmode="numeric"
                                pattern="[0-9]{7}"
                                maxlength="7"
                                placeholder="2302314"
                                data-student-id-input
                                required
                            >
                            <span class="mini">Use the 7-digit format.</span>
                            @if($shouldOpenCreate)
                                <x-field-error field="student_id_number" bag="studentCreate" />
                            @endif
                        </label>

                        <label>
                            Program
                            <select name="program_id" required>
                                <option value="">Select program</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ $shouldOpenCreate && (string) old('program_id') === (string) $program->id ? 'selected' : '' }}>
                                        {{ $program->code }} - {{ $program->name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="field-helper"></span>
                            @if($shouldOpenCreate)
                                <x-field-error field="program_id" bag="studentCreate" />
                            @endif
                        </label>
                    </div>

                    <div class="field-grid">
                        <label>
                            Year Level
                            <select name="year_level" required>
                                @foreach($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel }}" {{ $shouldOpenCreate && (string) old('year_level') === (string) $yearLevel ? 'selected' : '' }}>
                                        {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                    </option>
                                @endforeach
                            </select>
                            @if($shouldOpenCreate)
                                <x-field-error field="year_level" bag="studentCreate" />
                            @endif
                        </label>

                        <label>
                            Birthday
                            <input
                                type="date"
                                name="date_of_birth"
                                value="{{ $shouldOpenCreate ? old('date_of_birth') : '' }}"
                            >
                            @if($shouldOpenCreate)
                                <x-field-error field="date_of_birth" bag="studentCreate" />
                            @endif
                        </label>
                    </div>

                    <div class="field-grid">
                        <label class="password-wrapper">
                            Password
                            <div class="password-field">
                                <input
                                    type="password"
                                    name="password"
                                    id="student-create-password"
                                    maxlength="72"
                                    required
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-password-toggle
                                    data-target="student-create-password"
                                    aria-label="Show password"
                                    title="Show password"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                        fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="M1 12s4-6 11-6 11 6 11 6-4 6-11 6-11-6-11-6z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>

                            @if($shouldOpenCreate)
                                <x-field-error field="password" bag="studentCreate" />
                            @endif
                        </label>
                    </div>

                    <div class="form-actions modal-actions">
                        <button type="button" class="secondary" data-modal-close>Cancel</button>
                        <button
                            type="submit"
                            data-loading-button
                            data-loading-text="Creating Student..."
                        >
                            Create Student
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- EDIT MODALS --}}
        @foreach($students as $student)
            @php
                $studentUpdateFormKey = 'student-update-' . $student->id;
                $shouldOpenEdit = $activeFormKey === $studentUpdateFormKey;
                $studentNameParts = $student->user->studentNameParts();
            @endphp

            <div
                class="management-modal {{ $shouldOpenEdit ? 'is-open' : '' }}"
                id="student-edit-{{ $student->id }}"
                data-modal
            >
                <div class="management-modal-panel">
                    <div class="management-modal-header">
                        <div>
                            <div class="eyebrow">Edit Student</div>
                            <h2>{{ $student->displayName() }}</h2>
                        </div>

                        <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.students.update', $student) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form_key" value="{{ $studentUpdateFormKey }}">

                        <div class="field-grid">
                            <label>
                                First Name
                                <input type="text" name="first_name" value="{{ $shouldOpenEdit ? old('first_name', $studentNameParts['first_name']) : $studentNameParts['first_name'] }}" maxlength="60" required>
                                @if($shouldOpenEdit)
                                    <x-field-error field="first_name" bag="studentUpdate" />
                                @endif
                            </label>

                            <label>
                                Last Name
                                <input type="text" name="last_name" value="{{ $shouldOpenEdit ? old('last_name', $studentNameParts['last_name']) : $studentNameParts['last_name'] }}" maxlength="60" required>
                                @if($shouldOpenEdit)
                                    <x-field-error field="last_name" bag="studentUpdate" />
                                @endif
                            </label>
                        </div>

                        <div class="field-grid">
                            <label>
                                Middle Initial
                                <input type="text" name="middle_initial" value="{{ $shouldOpenEdit ? old('middle_initial', $studentNameParts['middle_initial']) : $studentNameParts['middle_initial'] }}" maxlength="1" placeholder="A">
                                @if($shouldOpenEdit)
                                    <x-field-error field="middle_initial" bag="studentUpdate" />
                                @endif
                            </label>

                            <label>
                                Extension
                                <select name="name_extension">
                                    <option value="">No extension</option>
                                    @foreach($studentNameExtensions as $extension)
                                        <option value="{{ $extension }}" {{ $shouldOpenEdit ? ((old('name_extension', $studentNameParts['name_extension']) === $extension) ? 'selected' : '') : (($studentNameParts['name_extension'] === $extension) ? 'selected' : '') }}>
                                            {{ $extension }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($shouldOpenEdit)
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
                                    value="{{ $shouldOpenEdit ? old('student_id_number', $student->student_id_number) : $student->student_id_number }}"
                                    inputmode="numeric"
                                    pattern="[0-9]{7}"
                                    maxlength="7"
                                    placeholder="2302314"
                                    data-student-id-input
                                    required
                                >
                                <span class="mini">Use the 7-digit format.</span>
                                @if($shouldOpenEdit)
                                    <x-field-error field="student_id_number" bag="studentUpdate" />
                                @endif
                            </label>

                            <label>
                                Program
                                <select name="program_id" required>
                                    <option value="">Select program</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" {{ $shouldOpenEdit ? (((string) old('program_id', $student->program_id) === (string) $program->id) ? 'selected' : '') : (($student->program_id === $program->id) ? 'selected' : '') }}>
                                            {{ $program->code }} - {{ $program->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <span class="field-helper"></span>
                                @if($shouldOpenEdit)
                                    <x-field-error field="program_id" bag="studentUpdate" />
                                @endif
                            </label>
                        </div>

                        <div class="field-grid">
                        <label>
                            Year Level
                            <select name="year_level" required>
                                @foreach($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel }}" {{ $shouldOpenEdit ? (((string) old('year_level', $student->year_level) === (string) $yearLevel) ? 'selected' : '') : (($student->year_level === $yearLevel) ? 'selected' : '') }}>
                                            {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                        </option>
                                    @endforeach
                                </select>
                                @if($shouldOpenEdit)
                                    <x-field-error field="year_level" bag="studentUpdate" />
                            @endif
                        </label>

                        <label>
                            Birthday
                            <input
                                type="date"
                                name="date_of_birth"
                                value="{{ $shouldOpenEdit ? old('date_of_birth', $student->date_of_birth?->toDateString()) : $student->date_of_birth?->toDateString() }}"
                            >
                            @if($shouldOpenEdit)
                                <x-field-error field="date_of_birth" bag="studentUpdate" />
                            @endif
                        </label>
                    </div>

                    <div class="field-grid">
                        <label class="password-wrapper">
                            Password
                            <div class="password-field">
                                    <input
                                        type="password"
                                        name="password"
                                        id="student-update-password-{{ $student->id }}"
                                        placeholder="Leave blank to keep the current password"
                                        maxlength="72"
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        data-password-toggle
                                        data-target="student-update-password-{{ $student->id }}"
                                        aria-label="Show password"
                                        title="Show password"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                            <path d="M1 12s4-6 11-6 11 6 11 6-4 6-11 6-11-6-11-6z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                    </button>
                                </div>

                                @if($shouldOpenEdit)
                                    <x-field-error field="password" bag="studentUpdate" />
                                @endif
                            </label>
                        </div>

                        <div class="form-actions modal-actions">
                            <button type="button" class="secondary" data-modal-close>Cancel</button>
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Updating Student..."
                            >
                                Update Student
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div
                class="management-modal"
                id="student-delete-{{ $student->id }}"
                data-modal
            >
                <div class="management-modal-panel">
                    <div class="management-modal-header">
                        <div>
                            <div class="eyebrow">Delete Student Account</div>
                            <h2>{{ $student->displayName() }}</h2>
                        </div>

                        <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.students.destroy', $student) }}">
                        @csrf
                        @method('DELETE')

                        <p class="callout error">
                            This permanently deletes the student account and linked clearance records. Type
                            <strong>DELETE {{ $student->student_id_number }}</strong> to confirm.
                        </p>

                        <label>
                            Confirmation
                            <input
                                type="text"
                                name="delete_confirmation"
                                autocomplete="off"
                                required
                            >
                            <x-field-error field="delete_confirmation" bag="studentDelete" />
                        </label>

                        <div class="form-actions modal-actions">
                            <button type="button" class="secondary" data-modal-close>Cancel</button>
                            <button type="submit" class="warn">Delete Student Account</button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach

        <div class="student-profile-drawer" id="studentProfileDrawer" hidden>
            <div class="student-profile-drawer-panel">
                <div class="student-profile-drawer-header">
                    <button type="button" class="secondary" id="closeStudentProfileDrawer">Close</button>
                    <a href="#" class="button secondary" id="openStudentProfilePage">Open Full Page</a>
                </div>
                <div id="studentProfileDrawerBody">
                    <div class="empty-state">Loading student profile...</div>
                </div>
            </div>
        </div>
    </div>

    @include('admin.partials.management-page-styles')
    @include('students.partials.profile-styles')

    @push('styles')
    <style>
        .student-filter-bar {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
        }

        .student-filter-bar input,
        .student-filter-bar select {
            width: 100%;
            margin: 0;
        }

        .student-filter-bar .button,
        .student-filter-bar .secondary-button {
            width: auto;
            white-space: nowrap;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .student-id-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 74px;
            padding: 7px 12px;
            border-radius: 999px;
            background: #dde8f7;
            color: #16385f;
            font-size: 13px;
        }

        .student-profile-link {
            text-decoration: none;
        }

        .student-profile-link:hover,
        .student-profile-link:focus-visible {
            color: #0e2742;
            text-decoration: underline;
        }

        .student-profile-drawer {
            position: fixed;
            inset: 0;
            z-index: 1300;
            display: flex;
            justify-content: flex-end;
            background: rgba(8, 26, 43, 0.42);
        }

        .student-profile-drawer[hidden] {
            display: none !important;
        }

        .student-profile-drawer-panel {
            width: min(920px, 100%);
            height: 100%;
            overflow-y: auto;
            padding: 22px;
            background: #f8f4ea;
            box-shadow: -18px 0 44px rgba(14, 39, 66, 0.2);
        }

        .student-profile-drawer-header {
            position: sticky;
            top: 0;
            z-index: 2;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin: -22px -22px 18px;
            padding: 16px 22px;
            background: rgba(248, 244, 234, 0.96);
            border-bottom: 1px solid #e4dacd;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 18px;
        }

        .pagination {
            display: flex;
            gap: 6px;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .pagination li {
            display: inline-block;
        }

        .pagination li a,
        .pagination li span {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            border: 1px solid #ddd;
            color: #1b3a6b;
            background: #fff;
            transition: 0.2s ease;
        }

        .pagination li a:hover {
            background: #1b3a6b;
            color: #fff;
        }

        .pagination li.active span {
            background: #d1a33b;
            color: #fff;
            border-color: #d1a33b;
            font-weight: bold;
        }

        .pagination li.disabled span {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .password-wrapper {
            display: grid;
            gap: 6px;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            width: 100%;
            padding-right: 52px;
        }

        .password-field .password-toggle {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            padding: 0;
            margin: 0;
            border: 0;
            border-radius: 0;
            background: transparent;
            color: var(--muted);
            box-shadow: none;
            cursor: pointer;
            z-index: 2;
        }

        .password-field .password-toggle:hover,
        .password-field .password-toggle:focus-visible {
            background: transparent;
            color: var(--navy);
            outline: none;
            transform: translateY(-50%);
        }

        .password-field .password-toggle svg {
            width: 18px;
            height: 18px;
            display: block;
            pointer-events: none;
        }

        .field-grid {
            align-items: start;
        }

        .field-grid > label {
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .field-grid > label .mini,
        .field-grid > label .field-helper {
            display: block;
            min-height: 36px;
            line-height: 1.35;
        }

        @media (max-width: 1100px) {
            .student-filter-bar {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .student-filter-bar {
                grid-template-columns: 1fr;
            }

            .student-filter-bar .button,
            .student-filter-bar .secondary-button {
                width: 100%;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-password-toggle]').forEach((toggleButton) => {
            const targetId = toggleButton.dataset.target;
            const passwordInput = document.getElementById(targetId);

            if (!passwordInput) return;

            toggleButton.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';

                passwordInput.type = isHidden ? 'text' : 'password';
                toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                toggleButton.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
            });
        });

        const normalizeStudentId = (value) => value.replace(/\D+/g, '').slice(0, 7);

        document.querySelectorAll('input[data-student-id-input]').forEach((input) => {
            input.addEventListener('beforeinput', (event) => {
                if (event.inputType === 'insertText' && event.data && /\D/.test(event.data)) {
                    event.preventDefault();
                }
            });

            input.addEventListener('input', () => {
                const normalized = normalizeStudentId(input.value);
                if (input.value !== normalized) input.value = normalized;
            });

            input.addEventListener('paste', (event) => {
                event.preventDefault();

                const clipboard = event.clipboardData || window.clipboardData;
                const pastedText = clipboard ? clipboard.getData('text') : '';
                const selectionStart = input.selectionStart ?? input.value.length;
                const selectionEnd = input.selectionEnd ?? input.value.length;

                input.value = normalizeStudentId(
                    input.value.slice(0, selectionStart) +
                    pastedText +
                    input.value.slice(selectionEnd)
                );
            });
        });

        const drawer = document.getElementById('studentProfileDrawer');
        const drawerBody = document.getElementById('studentProfileDrawerBody');
        const closeDrawerButton = document.getElementById('closeStudentProfileDrawer');
        const openFullPageLink = document.getElementById('openStudentProfilePage');
        const studentListUrl = window.location.href;

        const closeDrawer = () => {
            if (!drawer) return;

            drawer.hidden = true;
            document.body.style.overflow = '';

            if (window.location.href !== studentListUrl) {
                window.history.pushState({}, '', studentListUrl);
            }
        };

        document.querySelectorAll('.student-profile-link').forEach((link) => {
            link.addEventListener('click', async (event) => {
                if (!drawer || !drawerBody || !openFullPageLink) return;

                event.preventDefault();
                drawer.hidden = false;
                document.body.style.overflow = 'hidden';
                drawerBody.innerHTML = '<div class="empty-state">Loading student profile...</div>';
                openFullPageLink.href = link.dataset.studentProfileFullUrl || link.href;

                try {
                    const response = await fetch(link.dataset.studentProfileUrl, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to load the student profile.');
                    }

                    drawerBody.innerHTML = await response.text();
                    window.history.pushState({}, '', link.dataset.studentProfileFullUrl || link.href);
                } catch (error) {
                    drawerBody.innerHTML = '<div class="empty-state">Unable to load the student profile. Open the full page instead.</div>';
                }
            });
        });

        closeDrawerButton?.addEventListener('click', closeDrawer);
        drawer?.addEventListener('click', (event) => {
            if (event.target === drawer) closeDrawer();
        });
    });
    </script>
    @endpush
@endsection
