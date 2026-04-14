@extends('layouts.portal', [
    'title' => 'Students',
    'subtitle' => 'Create, search, and maintain student roster records.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))

    <div class="stack">
        @include('admin.partials.page-feedback')

        <section class="dashboard-section" style="margin-top: 0; padding-top: 0; border-top: 0;">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Accounts and Records</div>
                    <h2>Students</h2>
                    <p class="section-copy">Add new student accounts and manage the searchable student roster.</p>
                </div>
            </div>

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

                <div class="card" id="student-records">
                    <div class="eyebrow">Existing Students</div>
                    <h3>Student account records</h3>

                    <form method="GET" action="{{ route('admin.students.index') }}" class="roster-filter-bar">
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

                        <button type="submit" class="button">Apply</button>
                        <a href="{{ route('admin.students.index') }}" class="button secondary-button">Reset</a>
                    </form>

                    @if(!$hasStudents)
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
                                                <span class="mini">Use the 7-digit format, for example 2302314.</span>
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
                                            <label>
                                                Password
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
                    @endif
                </div>
            </div>
        </section>
    </div>

    @push('styles')
    <style>
        .compact-copy {
            margin-top: 0;
            margin-bottom: 14px;
            max-width: 60ch;
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
            .roster-filter-bar {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .roster-filter-bar {
                grid-template-columns: 1fr;
            }

            .roster-filter-bar .button {
                width: 100%;
            }
        }
    </style>
    @endpush

    @push('scripts')
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
                        input.value.slice(0, selectionStart) +
                        pastedText +
                        input.value.slice(selectionEnd)
                    );

                    input.value = nextValue;
                });
            });
        });
    </script>
    @endpush
@endsection
