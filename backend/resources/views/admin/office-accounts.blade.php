@extends('layouts.portal', [
    'title' => 'Office Accounts',
    'subtitle' => 'Create, filter, and update staff office accounts.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))

    <div class="admin-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header office-accounts-header">
            <div>
                <h1>OFFICE ACCOUNTS</h1>
                <p>Manage reusable staff accounts for designation assignments.</p>
            </div>

            <button type="button" class="button" id="openOfficeCreateModal">
                + Add Office Account
            </button>
        </section>

            <div class="office-accounts-layout">
                <div class="office-create-modal-backdrop" id="officeCreateModal" hidden>
                    <div class="admin-section-card office-create-modal" id="office-account-create-card">
                    <div class="eyebrow">Create Office Account</div>
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
                            <label class="password-wrapper">
                                Password
                                <div class="password-field">
                                    <input
                                        type="password"
                                        name="password"
                                        id="office-create-password"
                                        required
                                    >

                                    <button
                                        type="button"
                                        class="password-toggle"
                                        data-password-toggle
                                        data-target="office-create-password"
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
                        <div class="form-actions office-modal-actions">
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Creating Office Account..."
                            >
                                Create Office Account
                            </button>

                            <button
                                type="button"
                                class="button secondary secondary-button"
                                onclick="document.getElementById('officeCreateModal').hidden = true; document.body.style.overflow = '';"
                            >
                                Cancel
                            </button>
                        </div>

                        </form>
                </div>
            </div>

                <div class="admin-section-card full-height office-list-card" id="office-records">
                    <div class="eyebrow">Office Account List</div>

                    <form method="GET" action="{{ route('admin.office-accounts.index') }}" class="office-filter-bar">
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

                        <button type="submit" class="button">Apply Filters</button>
                        <a href="{{ route('admin.office-accounts.index') }}" class="button secondary secondary-button">Reset</a>
                    </form>

                    @if(!$hasOfficeAccounts)
                        <div class="empty-state office-empty-state">
                            <strong>No office accounts found.</strong>
                            <p class="mini">
                                No staff accounts matched your current filters. Try changing the name, username, scope, or office type filter.
                            </p>
                        </div>
                    @else
                        <div class="list flex-grow office-account-list">
                            <div class="mini" style="margin-bottom: 10px;">
                                Showing {{ $officeAccounts->firstItem() }} – {{ $officeAccounts->lastItem() }} of {{ $officeAccounts->total() }} office accounts
                            </div>
                            @foreach($officeAccounts as $officeAccount)
                                @php($officeUpdateFormKey = 'office-account-update-' . $officeAccount->id)
                                @php($editTypeOptions = $officeAccountEditTypeOptions[$officeAccount->id] ?? [])
                                <details class="record" {{ $activeFormKey === $officeUpdateFormKey ? 'open' : '' }}>
                                    <summary class="office-account-summary">
                                        <div>
                                            <strong class="office-account-name">{{ $officeAccount->display_name }}</strong>
                                            <div class="mini">Username: {{ $officeAccount->user->username }}</div>
                                        </div>

                                        <div class="office-account-badges">
                                            <span class="office-pill">{{ $officeAccount->officeTypeLabel() }}</span>
                                            <span class="office-pill">{{ $officeAccount->scopeSummaryLabel() }}</span>
                                        </div>
                                    </summary>

                                    <p class="mini assignment-note">
                                        Reusable staff account for designation assignment.
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
                                            <label class="password-wrapper">
                                                Password
                                                <div class="password-field">
                                                    <input
                                                        type="password"
                                                        name="password"
                                                        id="office-update-password-{{ $officeAccount->id }}"
                                                        placeholder="Leave blank to keep the current password"
                                                    >

                                                    <button
                                                        type="button"
                                                        class="password-toggle"
                                                        data-password-toggle
                                                        data-target="office-update-password-{{ $officeAccount->id }}"
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
                                        <div class="form-actions">
                                            <button
                                                type="submit"
                                                data-loading-button
                                                data-loading-text="Updating Office Account..."
                                            >
                                                Update Office Account
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            @endforeach
                            <div style="margin-top: 12px;">
                                <div class="pagination-wrapper">
                                    {{ $officeAccounts->links('pagination::bootstrap-5') }}
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    @push('styles')
    <style>
        .list {
            padding-bottom: 10px;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 16px;
        }

        .pagination-wrapper {
            margin-top: 20px;
        }

        .pagination {
            display: flex;
            gap: 6px;
            list-style: none;
            padding: 0;
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
            transition: color 0.15s ease;
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

        .admin-page-header {
            position: sticky;
            top: 90px;
            z-index: 20;
            background: #f7f1e6;
            padding-top: 18px;
            padding-bottom: 14px;
        }

        .compact-copy {
            margin-top: 0;
            margin-bottom: 14px;
            max-width: 60ch;
        }

        .office-filter-bar {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
            margin-bottom: 14px;
        }

        .office-filter-bar input,
        .office-filter-bar select,
        .office-filter-bar .button,
        .office-filter-bar .secondary-button {
            width: 100%;
            margin: 0;
        }

        .office-filter-bar .button,
        .office-filter-bar .secondary-button {
            width: auto;
            white-space: nowrap;
        }

        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
        }

        .office-accounts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
        }

        .office-accounts-layout {
            display: grid;
            gap: 18px;
        }

        .office-list-card {
            width: 100%;
        }

        .office-account-list {
            max-height: none !important;
            overflow: visible !important;
        }

        #office-records {
            max-height: none !important;
            overflow: visible !important;
        }

        .office-create-modal-backdrop[hidden] {
            display: none !important;
        }

        .office-create-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 24px;
            z-index: 1000;
        }

        .office-create-modal {
            width: min(720px, 100%);
            max-height: 90vh;
            overflow-y: auto;

            background: #ffffff;
            border-radius: 20px;
            padding: 24px 28px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.25);

            position: relative;
            z-index: 1001;
        }

        .office-account-summary {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 14px;
        }

        .office-account-name {
            color: var(--navy);
        }

        .office-account-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
        }

        .office-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            border-radius: 999px;
            background: #f3efe6;
            color: #1b3a6b;
            font-size: 12px;
            font-weight: 700;
        }

        .assignment-note {
            margin-top: 8px;
            color: #5b6578;
        }

        .office-empty-state p {
            margin-bottom: 0;
        }

        #closeOfficeCreateModal {
            margin-top: 12px;
            position: relative;
            z-index: 5;
            pointer-events: auto;
        }

        .office-modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 12px;
            align-items: center;
        }

        .office-modal-actions button {
            flex: none;
        }

        html {
            scrollbar-width: thin;
        }

        body::-webkit-scrollbar {
            width: 8px;
        }

        body::-webkit-scrollbar-thumb {
            background: rgba(15, 23, 42, 0.25);
            border-radius: 999px;
        }

        body::-webkit-scrollbar-track {
            background: transparent;
        }

        @media (max-width: 1100px) {
            .office-filter-bar {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .office-filter-bar {
                grid-template-columns: 1fr;
            }

            .office-filter-bar .button,
            .office-filter-bar .secondary-button {
                width: 100%;
            }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Password toggle
        document.querySelectorAll('[data-password-toggle]').forEach((toggleButton) => {
            const targetId = toggleButton.dataset.target;
            const passwordInput = document.getElementById(targetId);

            if (!passwordInput) {
                return;
            }

            toggleButton.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';

                passwordInput.type = isHidden ? 'text' : 'password';
                toggleButton.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
                toggleButton.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
                toggleButton.setAttribute('title', isHidden ? 'Hide password' : 'Show password');
            });
        });

        const openOfficeCreateModal = document.getElementById('openOfficeCreateModal');
        const closeOfficeCreateModal = document.getElementById('closeOfficeCreateModal');
        const officeCreateModal = document.getElementById('officeCreateModal');

        openOfficeCreateModal?.addEventListener('click', () => {
            officeCreateModal.hidden = false;
            document.body.style.overflow = 'hidden';
        });

        closeOfficeCreateModal?.addEventListener('click', () => {
            officeCreateModal.hidden = true;
            document.body.style.overflow = '';
        });

        officeCreateModal?.addEventListener('click', (event) => {
            if (event.target === officeCreateModal) {
                officeCreateModal.hidden = true;
                document.body.style.overflow = '';
            }
        });

        // Office scope behavior
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
