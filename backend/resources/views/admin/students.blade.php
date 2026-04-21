@extends('layouts.portal', [
    'title' => 'Students',
    'subtitle' => 'Create, search, and maintain student roster records.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))

    <div class="admin-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header">
            <div>
                <h1>STUDENTS</h1>
                <p>Add new student accounts and manage the searchable student roster.</p>
            </div>
        </section>

            <div class="grid-2 align-stretch">
                <div class="admin-section-card" id="student-create-card">
                    <div class="eyebrow">Create Student</div>
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
                                <span class="mini">Use the 7-digit format.</span>
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
                                <span class="field-helper"></span>
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
                            <label class="password-wrapper">
                                Password
                                <div class="password-field">
                                    <input
                                        type="password"
                                        name="password"
                                        id="student-create-password"
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

                                @if($activeFormKey === $studentCreateFormKey)
                                    <x-field-error field="password" bag="studentCreate" />
                                @endif
                            </label>
                        </div>
                        <div class="form-actions">
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

                <div class="admin-section-card full-height" id="student-records">
                    <div class="eyebrow">Existing Students</div>

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
                        No matching student records found.
                    </div>
                @else
                    <div class="list scrollable-list flex-grow">
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
                                                        <option value="{{ $extension }}" {{ $activeFormKey === $studentUpdateFormKey ? ((old('name_extension', $studentNameParts['name_extension']) === $extension) ? 'selected' : '') : (($studentNameParts['name_extension'] === $extension) ? 'selected' : '') }}>
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
                                                <span class="mini">Use the 7-digit format.</span>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="student_id_number" bag="studentUpdate" />
                                                @endif
                                            </label>
                                            <label>
                                                Program
                                                <select name="program_id" required>
                                                    <option value="">Select program</option>
                                                    @foreach($programs as $program)
                                                        <option value="{{ $program->id }}" {{ $activeFormKey === $studentUpdateFormKey ? (((string) old('program_id', $student->program_id) === (string) $program->id) ? 'selected' : '') : (($student->program_id === $program->id) ? 'selected' : '') }}>
                                                            {{ $program->code }} - {{ $program->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <span class="field-helper"></span>
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
                                                        <option value="{{ $yearLevel }}" {{ $activeFormKey === $studentUpdateFormKey ? (((string) old('year_level', $student->year_level) === (string) $yearLevel) ? 'selected' : '') : (($student->year_level === $yearLevel) ? 'selected' : '') }}>
                                                            {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="year_level" bag="studentUpdate" />
                                                @endif
                                            </label>
                                            <label class="password-wrapper">
                                                Password
                                                <div class="password-field">
                                                    <input
                                                        type="password"
                                                        name="password"
                                                        id="student-update-password-{{ $student->id }}"
                                                        placeholder="Leave blank to keep the current password"
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

                                                @if($activeFormKey === $studentUpdateFormKey)
                                                    <x-field-error field="password" bag="studentUpdate" />
                                                @endif
                                            </label>
                                        </div>
                                        <div class="form-actions">
                                            <button
                                                type="submit"
                                                data-loading-button
                                                data-loading-text="Updating Student..."
                                            >
                                                Update Student
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

    @push('styles')
    <style>

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
        
        .compact-copy {
            margin-top: 0;
            margin-bottom: 14px;
            max-width: 60ch;
        }

        .student-filter-bar {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
        }

        .student-filter-bar input,
        .student-filter-bar select,
        .student-filter-bar .button,
        .student-filter-bar .secondary-button {
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

    });
    </script>
    @endpush
@endsection
