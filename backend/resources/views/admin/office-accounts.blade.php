@extends('layouts.portal', [
    'title' => 'Office Accounts',
    'subtitle' => 'Create, filter, and update staff office accounts.',
])

@section('page')
    @php
        $activeFormKey = old('_form_key');
        $officeCreateFormKey = 'office-account-create';
        $shouldOpenCreate = $activeFormKey === $officeCreateFormKey;
    @endphp

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>Office Accounts</h1>
                <p>Manage reusable staff accounts for designation assignments.</p>
            </div>

            <button
                type="button"
                class="management-primary-action"
                data-modal-open="office-account-create-card"
            >
                + Add Office Account
            </button>
        </section>

        <section class="admin-section-card management-card" id="office-records">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Office Account List</div>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.office-accounts.index') }}" class="office-filter-bar">
                <input
                    type="text"
                    name="office_search"
                    value="{{ $officeSearch }}"
                    placeholder="Search by name, username, or scope"
                    maxlength="100"
                >

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
                <div class="mini">
                    Showing {{ $officeAccounts->firstItem() }} – {{ $officeAccounts->lastItem() }} of {{ $officeAccounts->total() }} office accounts
                </div>

                <div class="management-table-wrap">
                    <table class="management-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Display Name</th>
                                <th>Username</th>
                                <th>Office Type</th>
                                <th>Scope</th>
                                <th class="management-action-col">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($officeAccounts as $officeAccount)
                                <tr>
                                    <td>
                                        <div class="account-avatar">
                                            @if($officeAccount->user->profilePhotoUrl())
                                                <img src="{{ $officeAccount->user->profilePhotoUrl() }}" alt="{{ $officeAccount->display_name }} profile picture">
                                            @else
                                                <span>{{ strtoupper(substr($officeAccount->display_name, 0, 1)) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="office-profile-name table-main-text"
                                            data-modal-open="office-view-{{ $officeAccount->id }}"
                                        >
                                            {{ $officeAccount->display_name }}
                                        </button>
                                    </td>
                                    <td>{{ $officeAccount->user->username }}</td>
                                    <td>
                                        <span class="office-pill" title="{{ $officeAccount->officeTypeLabel() }}">
                                            {{ $officeAccount->officeTypeLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="office-pill" title="{{ $officeAccount->scopeSummaryLabel() }}">
                                            {{ $officeAccount->scopeSummaryLabel() }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="office-action-stack">
                                            <button
                                                type="button"
                                                class="button secondary table-action-button"
                                                data-modal-open="office-view-{{ $officeAccount->id }}"
                                            >
                                                View
                                            </button>

                                            <button
                                                type="button"
                                                class="button secondary table-action-button"
                                                data-modal-open="office-edit-{{ $officeAccount->id }}"
                                            >
                                                Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    {{ $officeAccounts->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </section>

        {{-- CREATE MODAL --}}
        <div
            class="management-modal {{ $shouldOpenCreate ? 'is-open' : '' }}"
            id="office-account-create-card"
            data-modal
        >
            <div class="management-modal-panel">
                <div class="management-modal-header">
                    <div>
                        <div class="eyebrow">Create Office Account</div>
                        <h2>Add Office Account</h2>
                    </div>

                    <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.office-accounts.store') }}" data-office-account-form>
                    @csrf
                    <input type="hidden" name="_form_key" value="{{ $officeCreateFormKey }}">

                    <div class="field-grid">
                        <label>
                            Display Name
                            <input
                                type="text"
                                name="display_name"
                                value="{{ $shouldOpenCreate ? old('display_name') : '' }}"
                                maxlength="100"
                                required
                            >
                            @if($shouldOpenCreate)
                                <x-field-error field="display_name" bag="officeAccountCreate" />
                            @endif
                        </label>

                        <label>
                            Username
                            <input
                                type="text"
                                name="username"
                                value="{{ $shouldOpenCreate ? old('username') : '' }}"
                                maxlength="60"
                                required
                            >
                            @if($shouldOpenCreate)
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
                                    <option value="{{ $value }}" {{ $shouldOpenCreate && old('office_type') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if($shouldOpenCreate)
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
                                    maxlength="72"
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

                            @if($shouldOpenCreate)
                                <x-field-error field="password" bag="officeAccountCreate" />
                            @endif
                        </label>
                    </div>

                    <p class="mini" data-scope-note>
                        Choose an office type to see which fields are needed.
                    </p>

                    <div class="field-grid">
                        <label>
                            Program Scope
                            <select name="program_id" data-program-scope-select>
                                <option value="">Select program scope</option>
                                @foreach($programs as $program)
                                    <option value="{{ $program->id }}" {{ $shouldOpenCreate && (string) old('program_id') === (string) $program->id ? 'selected' : '' }}>
                                        {{ $program->code }}
                                    </option>
                                @endforeach
                            </select>
                            @if($shouldOpenCreate)
                                <x-field-error field="program_id" bag="officeAccountCreate" />
                            @endif
                        </label>

                        <label>
                            Year Level Scope
                            <select name="year_level" data-year-level-scope-select>
                                <option value="">Select year level scope</option>
                                @foreach($yearLevels as $yearLevel)
                                    <option value="{{ $yearLevel }}" {{ $shouldOpenCreate && (string) old('year_level') === (string) $yearLevel ? 'selected' : '' }}>
                                        {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                    </option>
                                @endforeach
                            </select>
                            @if($shouldOpenCreate)
                                <x-field-error field="year_level" bag="officeAccountCreate" />
                            @endif
                        </label>
                    </div>

                    <div class="form-actions modal-actions">
                        <button type="button" class="secondary" data-modal-close>Cancel</button>
                        <button
                            type="submit"
                            data-loading-button
                            data-loading-text="Creating Office Account..."
                        >
                            Create Office Account
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- EDIT MODALS --}}
        @foreach($officeAccounts as $officeAccount)
            @php
                $officeUpdateFormKey = 'office-account-update-' . $officeAccount->id;
                $shouldOpenEdit = $activeFormKey === $officeUpdateFormKey;
                $editTypeOptions = $officeAccountEditTypeOptions[$officeAccount->id] ?? [];
                $activeDesignations = $officeAccount->user->activeOfficeDesignations;
            @endphp

            <div
                class="management-modal"
                id="office-view-{{ $officeAccount->id }}"
                data-modal
            >
                <div class="management-modal-panel office-profile-modal">
                    <div class="management-modal-header">
                        <div>
                            <div class="eyebrow">Office Profile</div>
                            <h2>{{ $officeAccount->display_name }}</h2>
                        </div>

                        <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                    </div>

                    <div class="office-profile-summary">
                        <div class="account-avatar large">
                            @if($officeAccount->user->profilePhotoUrl())
                                <img src="{{ $officeAccount->user->profilePhotoUrl() }}" alt="{{ $officeAccount->display_name }} profile picture">
                            @else
                                <span>{{ strtoupper(substr($officeAccount->display_name, 0, 1)) }}</span>
                            @endif
                        </div>

                        <div class="office-profile-heading">
                            <strong>{{ $officeAccount->display_name }}</strong>
                            <span>{{ $officeAccount->user->username }}</span>
                        </div>
                    </div>

                    <dl class="office-profile-facts">
                        <div>
                            <dt>Account Type</dt>
                            <dd>{{ $officeAccount->officeTypeLabel() }}</dd>
                        </div>
                        <div>
                            <dt>Scope</dt>
                            <dd>{{ $officeAccount->scopeSummaryLabel() }}</dd>
                        </div>
                        <div>
                            <dt>Active Assignments</dt>
                            <dd>{{ $activeDesignations->count() }}</dd>
                        </div>
                    </dl>

                    <div class="office-profile-assignments">
                        <div class="eyebrow">Assigned Designations</div>
                        @forelse($activeDesignations as $designation)
                            <div class="office-assignment-row">
                                <strong>{{ $designation->display_name }}</strong>
                                <span>{{ $designation->scopeLabel() ?: 'Whole school' }}</span>
                            </div>
                        @empty
                            <div class="empty-state compact-empty-state">
                                No active designation assignment.
                            </div>
                        @endforelse
                    </div>

                    <div class="form-actions modal-actions">
                        <button type="button" class="secondary" data-modal-close>Close</button>
                        <button
                            type="button"
                            data-modal-open="office-edit-{{ $officeAccount->id }}"
                            data-modal-close
                        >
                            Edit Account
                        </button>
                    </div>
                </div>
            </div>

            <div
                class="management-modal {{ $shouldOpenEdit ? 'is-open' : '' }}"
                id="office-edit-{{ $officeAccount->id }}"
                data-modal
            >
                <div class="management-modal-panel">
                    <div class="management-modal-header">
                        <div>
                            <div class="eyebrow">Edit Office Account</div>
                            <h2>{{ $officeAccount->display_name }}</h2>
                        </div>

                        <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.office-accounts.update', $officeAccount) }}" data-office-account-form>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form_key" value="{{ $officeUpdateFormKey }}">

                        <div class="field-grid">
                            <div>
                                <div class="eyebrow">Current Account Type</div>
                                <p class="modal-readonly-text">{{ $officeAccount->officeTypeLabel() }}</p>
                            </div>

                            <div>
                                <div class="eyebrow">Current Scope</div>
                                <p class="modal-readonly-text">{{ $officeAccount->scopeSummaryLabel() }}</p>
                            </div>
                        </div>

                        <div class="field-grid">
                            <label>
                                Display Name
                                <input
                                    type="text"
                                    name="display_name"
                                    value="{{ $shouldOpenEdit ? old('display_name', $officeAccount->display_name) : $officeAccount->display_name }}"
                                    maxlength="100"
                                    required
                                >
                                @if($shouldOpenEdit)
                                    <x-field-error field="display_name" bag="officeAccountUpdate" />
                                @endif
                            </label>

                            <label>
                                Username
                                <input
                                    type="text"
                                    name="username"
                                    value="{{ $shouldOpenEdit ? old('username', $officeAccount->user->username) : $officeAccount->user->username }}"
                                    maxlength="60"
                                    required
                                >
                                @if($shouldOpenEdit)
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
                                        <option value="{{ $value }}" {{ $shouldOpenEdit ? ((old('office_type', $officeAccount->office_type) === $value) ? 'selected' : '') : (($officeAccount->office_type === $value) ? 'selected' : '') }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($shouldOpenEdit)
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
                                        maxlength="72"
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

                                @if($shouldOpenEdit)
                                    <x-field-error field="password" bag="officeAccountUpdate" />
                                @endif
                            </label>
                        </div>

                        <p class="mini" data-scope-note>
                            {{ $officeTypeScopeMetadata[$officeAccount->office_type]['note'] ?? 'Choose an office type to set the staff account\'s usual scope.' }}
                        </p>

                        <div class="field-grid">
                            <label>
                                Program Scope
                                <select name="program_id" data-program-scope-select>
                                    <option value="">Select program scope</option>
                                    @foreach($programs as $program)
                                        <option value="{{ $program->id }}" {{ ($shouldOpenEdit ? (string) old('program_id', $officeAccount->program_id) === (string) $program->id : $officeAccount->program_id === $program->id) ? 'selected' : '' }}>
                                            {{ $program->code }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($shouldOpenEdit)
                                    <x-field-error field="program_id" bag="officeAccountUpdate" />
                                @endif
                            </label>

                            <label>
                                Year Level Scope
                                <select name="year_level" data-year-level-scope-select>
                                    <option value="">Select year level scope</option>
                                    @foreach($yearLevels as $yearLevel)
                                        <option value="{{ $yearLevel }}" {{ ($shouldOpenEdit ? (string) old('year_level', $officeAccount->year_level) === (string) $yearLevel : $officeAccount->year_level === $yearLevel) ? 'selected' : '' }}>
                                            {{ $yearLevel }}{{ ['st', 'nd', 'rd', 'th'][$yearLevel - 1] ?? 'th' }} Year
                                        </option>
                                    @endforeach
                                </select>
                                @if($shouldOpenEdit)
                                    <x-field-error field="year_level" bag="officeAccountUpdate" />
                                @endif
                            </label>
                        </div>

                        <div class="form-actions modal-actions">
                            <button type="button" class="secondary" data-modal-close>Cancel</button>
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Updating Office Account..."
                            >
                                Update Office Account
                            </button>
                        </div>
                    </form>

                    <form
                        method="POST"
                        action="{{ route('admin.office-accounts.profile-photo.update', $officeAccount) }}"
                        enctype="multipart/form-data"
                        class="profile-photo-form"
                    >
                        @csrf
                        <div class="profile-photo-edit-row">
                            <div class="account-avatar large">
                                @if($officeAccount->user->profilePhotoUrl())
                                    <img src="{{ $officeAccount->user->profilePhotoUrl() }}" alt="{{ $officeAccount->display_name }} profile picture">
                                @else
                                    <span>{{ strtoupper(substr($officeAccount->display_name, 0, 1)) }}</span>
                                @endif
                            </div>

                            <label>
                                Profile Picture
                                <input
                                    type="file"
                                    name="profile_photo"
                                    accept="image/jpeg,image/png,image/webp"
                                    required
                                >
                                <span class="mini">JPG, PNG, or WEBP. Max 2 MB.</span>
                                <x-field-error field="profile_photo" bag="officeAccountPhoto" />
                            </label>
                        </div>

                        <div class="form-actions modal-actions">
                            <button
                                type="submit"
                                class="secondary"
                                data-loading-text="Uploading Photo..."
                            >
                                Upload Profile Picture
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    @include('admin.partials.management-page-styles')

    @push('styles')
    <style>
        .office-filter-bar {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr) minmax(0, 1fr) auto auto;
            gap: 10px;
            align-items: center;
        }

        .office-filter-bar input,
        .office-filter-bar select {
            width: 100%;
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

        .office-pill {
            display: inline-flex;
            max-width: 260px;
            align-items: center;
            padding: 7px 12px;
            border-radius: 999px;
            background: var(--bg-app);
            color: var(--brand-navy);
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .office-profile-name {
            display: inline;
            max-width: 280px;
            padding: 0;
            border: 0;
            background: transparent;
            color: var(--text-primary);
            font: inherit;
            font-weight: 800;
            text-align: left;
            overflow-wrap: anywhere;
            cursor: pointer;
        }

        .office-profile-name:hover,
        .office-profile-name:focus-visible {
            color: var(--brand-navy);
            text-decoration: underline;
        }

        .office-action-stack {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .account-avatar {
            width: 42px;
            height: 42px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            overflow: hidden;
            background: linear-gradient(135deg, var(--text-primary) 0%, var(--brand-navy) 100%);
            color: var(--bg-surface);
            font-weight: 900;
            border: 2px solid var(--border-subtle);
            box-shadow: 0 6px 14px rgba(24, 58, 99, 0.12);
        }

        .account-avatar.large {
            width: 72px;
            height: 72px;
            font-size: 1.4rem;
        }

        .account-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .office-profile-modal {
            max-width: 720px;
        }

        .office-profile-summary {
            display: flex;
            gap: 16px;
            align-items: center;
            padding: 16px;
            border: 1px solid var(--border-subtle);
            border-radius: 18px;
            background: var(--bg-surface);
            min-width: 0;
        }

        .office-profile-heading {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        .office-profile-heading strong {
            color: var(--text-primary);
            font-size: 1.2rem;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .office-profile-heading span {
            color: var(--text-muted);
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .office-profile-facts {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            margin: 14px 0;
        }

        .office-profile-facts div,
        .office-assignment-row {
            padding: 12px;
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            background: var(--bg-surface);
            min-width: 0;
        }

        .office-profile-facts dt {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .office-profile-facts dd {
            margin: 6px 0 0;
            color: var(--text-primary);
            font-weight: 800;
            line-height: 1.32;
            overflow-wrap: anywhere;
        }

        .office-profile-assignments {
            display: grid;
            gap: 10px;
        }

        .office-assignment-row {
            display: flex;
            justify-content: space-between;
            gap: 14px;
        }

        .office-assignment-row strong,
        .office-assignment-row span {
            overflow-wrap: anywhere;
            min-width: 0;
        }

        .office-assignment-row span {
            color: var(--text-muted);
            font-weight: 700;
            text-align: right;
        }

        .compact-empty-state {
            padding: 14px;
        }

        .profile-photo-form {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid var(--border-subtle);
        }

        .profile-photo-edit-row {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 16px;
            align-items: center;
        }

        .modal-readonly-text {
            margin: 6px 0 0;
            color: var(--text-primary);
            font-weight: 700;
        }

        .office-empty-state p {
            margin-bottom: 0;
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

        .pagination li a,
        .pagination li span {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            border: 1px solid var(--border-subtle);
            color: var(--brand-navy);
            background: var(--bg-surface);
        }

        .pagination li a:hover {
            background: var(--brand-navy);
            color: var(--bg-surface);
        }

        .pagination li.active span {
            background: var(--brand-gold);
            color: var(--bg-surface);
            border-color: var(--brand-gold);
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

            .office-profile-summary,
            .office-assignment-row {
                align-items: flex-start;
                flex-direction: column;
            }

            .office-profile-facts {
                grid-template-columns: 1fr;
            }

            .office-assignment-row span {
                text-align: left;
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



