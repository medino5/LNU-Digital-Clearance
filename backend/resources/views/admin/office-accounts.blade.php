@extends('layouts.portal', [
    'title' => 'Office Accounts',
    'subtitle' => 'Create, filter, and update staff office accounts.',
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
                    <h2>Office Accounts</h2>
                    <p class="section-copy">Manage reusable staff accounts for non-student designation assignment.</p>
                </div>
            </div>

            <div class="grid-2">
                <div class="card" id="office-account-create-card">
                    <div class="eyebrow">Create Office Account</div>
                    <h3>Create a staff account</h3>
                    <p class="section-copy compact-copy">Create the account first, then assign it to an eligible designation from the routing page.</p>
                    @php($officeCreateFormKey = 'office-account-create')
                    <form method="POST" action="{{ route('admin.office-accounts.store') }}" data-office-account-form>
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
                                <select name="office_type" data-office-type-select required>
                                    <option value="">Select office type</option>
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
                            Choose an office type to see which fields are needed.
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

                <div class="card" id="office-records">
                    <div class="eyebrow">Office List</div>
                    <h3>Staff office accounts</h3>

                    <form method="GET" action="{{ route('admin.office-accounts.index') }}" class="roster-filter-bar">
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

                        <button type="submit" class="button">Apply</button>
                        <a href="{{ route('admin.office-accounts.index') }}" class="button secondary-button">Reset</a>
                    </form>

                    @if(!$hasOfficeAccounts)
                        <div class="record">
                            <p class="muted" style="margin: 0;">No matching records found.</p>
                        </div>
                    @else
                        <div class="list scrollable-list">
                            @foreach($officeAccounts as $officeAccount)
                                @php($officeUpdateFormKey = 'office-account-update-' . $officeAccount->id)
                                @php($editTypeOptions = $officeAccountEditTypeOptions[$officeAccount->id] ?? [])
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
                                                <select name="office_type" data-office-type-select required>
                                                    <option value="">Select office type</option>
                                                    @foreach($editTypeOptions as $value => $label)
                                                        <option value="{{ $value }}" {{ $activeFormKey === $officeUpdateFormKey ? ((old('office_type', $officeAccount->office_type) === $value) ? 'selected' : '') : (($officeAccount->office_type === $value) ? 'selected' : '') }}>
                                                            {{ $label }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @if($activeFormKey === $officeUpdateFormKey)
                                                    <x-field-error field="office_type" bag="officeAccountUpdate" />
                                                @endif
                                            </label>
                                            <label>
                                                Password
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
                                                        <option value="{{ $program->id }}" {{ ($activeFormKey === $officeUpdateFormKey ? (string) old('program_id', $officeAccount->program_id) === (string) $program->id : $officeAccount->program_id === $program->id) ? 'selected' : '' }}>
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
                                                        <option value="{{ $yearLevel }}" {{ ($activeFormKey === $officeUpdateFormKey ? (string) old('year_level', $officeAccount->year_level) === (string) $yearLevel : $officeAccount->year_level === $yearLevel) ? 'selected' : '' }}>
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
            const officeTypeScopeMetadata = @json($officeTypeScopeMetadata);

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
        });
    </script>
    @endpush
@endsection
