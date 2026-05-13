<section id="routing-configuration" class="admin-section-card routing-management-card">
    <div class="routing-page-header">
        <div>
            <h1>Routing Configuration</h1>
            <p>Manage clearance routes, office designations, and active holders.</p>
        </div>
    </div>

    <div class="routing-create-panel" id="designation-create">
        <div class="routing-create-copy">
            <span class="eyebrow">Routing Offices</span>
            <h2>Create Routing Office</h2>
        </div>

        <form method="POST" action="{{ route('admin.office-designations.store') }}" class="routing-create-form">
            @csrf
            <input type="hidden" name="_form_key" value="designation-create">

            <label>
                <span>Designation Type</span>
                <select name="office_type" data-designation-type required>
                    @foreach($designationTypeOptions as $value => $label)
                        <option value="{{ $value }}" @selected(old('office_type') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-field-error field="office_type" bag="designationCreate" />
            </label>

            <label data-designation-program-scope>
                <span>Program Scope</span>
                <select name="program_id">
                    <option value="">Select program</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" @selected((string) old('program_id') === (string) $program->id)>
                            {{ $program->code }} - {{ $program->name }}
                        </option>
                    @endforeach
                </select>
                <x-field-error field="program_id" bag="designationCreate" />
            </label>

            <label data-designation-year-scope>
                <span>Year Level Scope</span>
                <select name="year_level">
                    <option value="">Select year</option>
                    @foreach([1, 2, 3, 4] as $yearLevel)
                        <option value="{{ $yearLevel }}" @selected((string) old('year_level') === (string) $yearLevel)>
                            Year {{ $yearLevel }}
                        </option>
                    @endforeach
                </select>
                <x-field-error field="year_level" bag="designationCreate" />
            </label>

            <label>
                <span>Display Name</span>
                <input
                    type="text"
                    name="display_name"
                    value="{{ old('display_name') }}"
                    maxlength="120"
                    placeholder="Optional custom name"
                >
                <x-field-error field="display_name" bag="designationCreate" />
            </label>

            <button type="submit" data-loading-button data-loading-text="Saving...">
                Save Routing Office
            </button>
        </form>
    </div>

    <div class="routing-records-card">
        <div class="routing-records-header">
            <span class="eyebrow">Designation Records</span>

            <div class="routing-filter-bar">
                <input type="search" placeholder="Search designation" data-routing-search>

                <select data-routing-program>
                    <option value="">All program scopes</option>
                    @foreach($designations->pluck('program.code')->filter()->unique()->sort()->values() as $programCode)
                        <option value="{{ $programCode }}">{{ $programCode }}</option>
                    @endforeach
                </select>

                <select data-routing-year>
                    <option value="">All year levels</option>
                    @foreach($designations->pluck('year_level')->filter()->unique()->sort()->values() as $yearLevel)
                        <option value="{{ $yearLevel }}">Year {{ $yearLevel }}</option>
                    @endforeach
                </select>

                <select data-routing-status>
                    <option value="">All statuses</option>
                    <option value="assigned">Assigned</option>
                    <option value="unassigned">Unassigned</option>
                </select>

                <button type="button" class="button secondary routing-reset-button" data-routing-reset>
                    Reset
                </button>
            </div>
        </div>

        <div class="routing-empty-filter-state" data-routing-empty hidden>
            No designations match the current filters.
        </div>

        <div class="routing-table-wrap">
            <table class="routing-table">
                <thead>
                    <tr>
                        <th>Designation</th>
                        <th>Group / Scope</th>
                        <th>Current Holder</th>
                        <th>Status</th>
                        <th>Holder Assignment</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($designations as $designation)
                        @php
                            $designationFormKey = 'designation-assignment-' . $designation->id;
                            $currentAssignment = $designation->getRelation('current_assignment');
                            $currentUser = $currentAssignment?->user;
                            $currentOfficeAccount = $currentUser?->officeAccount;
                            $currentStudentProfile = $currentUser?->studentProfile;

                            $currentHolderName = $currentOfficeAccount?->display_name ?? $currentUser?->formattedName();

                            $currentHolderType = $currentOfficeAccount
                                ? 'Office Account'
                                : ($currentStudentProfile ? 'Student Holder' : null);

                            $currentHolderMeta = collect([
                                $currentOfficeAccount?->officeTypeLabel(),
                                $currentOfficeAccount?->scopeSummaryLabel(),
                                $currentStudentProfile?->program?->code,
                                $currentStudentProfile?->year_level ? 'Year ' . $currentStudentProfile->year_level : null,
                            ])->filter()->implode(' / ');

                            $scopeLabel = $designation->scopeLabel() ?? 'Whole school';
                            $statusValue = $currentAssignment ? 'assigned' : 'unassigned';
                            $programFilterValue = $designation->program?->code ?? '';
                            $yearFilterValue = $designation->year_level ?? '';
                        @endphp

                        <tr
                            class="routing-row {{ $currentAssignment ? '' : 'routing-row-unassigned' }}"
                            data-routing-row
                            data-routing-name="{{ strtolower($designation->display_name) }}"
                            data-routing-program="{{ $programFilterValue }}"
                            data-routing-year="{{ $yearFilterValue }}"
                            data-routing-status="{{ $statusValue }}"
                        >
                            <td>
                                <div class="routing-designation-name">
                                    {{ $designation->display_name }}
                                </div>
                                <div class="routing-muted">
                                    {{ $designation->officeTypeLabel() }}
                                </div>
                            </td>

                            <td>
                                <div class="routing-scope-stack">
                                    <span class="routing-scope-pill">
                                        {{ $scopeLabel }}
                                    </span>
                                    <div class="routing-muted">
                                        {{ $designation->officeTypeLabel() }}
                                    </div>
                                </div>
                            </td>

                            <td>
                                @if($currentAssignment)
                                    <div class="routing-holder-name">
                                        {{ $currentHolderName }}
                                    </div>

                                    <div class="routing-muted">
                                        {{ $currentHolderType }}
                                        @if($currentHolderMeta)
                                            / {{ $currentHolderMeta }}
                                        @endif
                                    </div>
                                @else
                                    <div class="routing-holder-unassigned">
                                        No current holder
                                    </div>
                                    <div class="routing-muted">
                                        Ready for assignment
                                    </div>
                                @endif
                            </td>

                            <td>
                                @if($currentAssignment)
                                    <span class="routing-status-badge assigned">Assigned</span>
                                @else
                                    <span class="routing-status-badge unassigned">Unassigned</span>
                                @endif
                            </td>

                            <td>
                                <div class="routing-row-actions">
                                    <button
                                        type="button"
                                        class="button secondary routing-assign-trigger"
                                        data-designation-name="{{ $designation->display_name }}"
                                        data-designation-scope="{{ $scopeLabel }}"
                                        data-form-key="{{ $designationFormKey }}"
                                        data-current-user-id="{{ $currentUser?->id }}"
                                        data-assignment-action="{{ route('admin.office-designations.assignment.update', $designation) }}"
                                        data-eligible-url="{{ route('admin.office-designations.eligible-users', $designation) }}"
                                    >
                                        {{ $currentAssignment ? 'Change Holder' : 'Assign Holder' }}
                                    </button>

                    <form
                        method="POST"
                        action="{{ route('admin.office-designations.destroy', $designation) }}"
                                        data-routing-delete-form
                                        data-routing-delete-name="{{ $designation->display_name }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button ghost routing-remove-button">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">No active designations yet.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="routing-modal-backdrop" id="routingAssignmentModal" hidden>
        <div class="routing-modal" role="dialog" aria-modal="true" aria-labelledby="routingAssignmentTitle">
            <div class="routing-modal-header">
                <div>
                    <div class="eyebrow">Holder Assignment</div>
                    <h2 id="routingAssignmentTitle">Assign Holder</h2>
                    <p id="routingAssignmentScope">Loading scope...</p>
                </div>
                <button type="button" class="modal-close-button" data-routing-assignment-close>&times;</button>
            </div>

            <form method="POST" id="routingAssignmentForm" class="routing-assignment-form">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form_key" id="routingAssignmentFormKey">

                <label>
                    <span>Search Eligible Holder</span>
                    <input
                        type="search"
                        id="routingAssignmentSearch"
                        placeholder="Type a name or student number"
                        autocomplete="off"
                    >
                </label>

                <label>
                    <span>Eligible Holder</span>
                    <select name="user_id" id="routingAssignmentUserSelect" required disabled>
                        <option value="">Loading eligible holders...</option>
                    </select>
                </label>

                <p class="mini routing-no-eligible" id="routingAssignmentHelp">
                    Candidate list loads only when needed to keep this page fast.
                </p>

                <x-field-error field="user_id" bag="designationAssignment" />

                <div class="routing-modal-actions">
                    <button type="button" class="secondary" data-routing-assignment-close>Cancel</button>
                    <button type="submit" data-loading-button data-loading-text="Saving...">
                        Save Holder
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('styles')
<style>
    #routing-configuration {
        display: grid;
        gap: 1.5rem;
        padding: 0;
        width: 100%;
        max-width: none;
        background: transparent;
        border: 0;
        box-shadow: none;
    }

    .routing-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
        width: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        box-shadow: none;
    }

    .routing-page-header h1 {
        margin: 0;
        color: var(--text-primary);
        font-size: 1.875rem;
        line-height: 1.1;
        letter-spacing: -0.03em;
        font-weight: 600;
        text-transform: none;
    }

    .routing-page-header p {
        margin: 0.375rem 0 0;
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .routing-create-panel {
        display: grid;
        grid-template-columns: minmax(180px, 0.7fr) minmax(0, 2.3fr);
        gap: 1.5rem;
        align-items: end;
        padding: 1.5rem;
        border: 1px solid var(--border-subtle);
        border-radius: 0.75rem;
        background: var(--bg-surface);
        box-shadow: var(--card-shadow);
    }

    .routing-create-copy h2 {
        margin: 4px 0 0;
        color: var(--text-primary);
        font-size: 1.25rem;
    }

    .routing-create-form {
        display: grid;
        grid-template-columns: repeat(4, minmax(150px, 1fr)) auto;
        gap: 1rem;
        align-items: start;
    }

    .routing-create-form label,
    .routing-assignment-form label {
        display: grid;
        gap: 0.375rem;
    }

    .routing-create-form label > span,
    .routing-assignment-form label > span {
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 500;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .routing-create-form label:has([required]) > span::after,
    .routing-assignment-form label:has([required]) > span::after {
        content: " *";
        color: var(--status-danger-text);
    }

    .routing-create-form input,
    .routing-create-form select,
    .routing-assignment-form input,
    .routing-assignment-form select {
        width: 100%;
        height: 2.5rem;
        margin: 0;
        border: 1px solid var(--border-subtle);
        border-radius: 0.5rem;
        background: var(--bg-surface);
        color: var(--text-primary);
        font-size: 14px;
        text-transform: none;
        letter-spacing: normal;
        font-weight: 600;
    }

    .routing-create-form button {
        align-self: end;
        min-height: 2.5rem;
        border-radius: 0.5rem;
        white-space: nowrap;
    }

    .routing-records-card {
        display: grid;
        gap: 1rem;
        width: 100%;
        padding: 0;
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }

    .routing-records-header {
        display: grid;
        gap: 1rem;
    }

    .routing-filter-bar {
        display: grid;
        grid-template-columns: minmax(240px, 1.4fr) repeat(3, minmax(150px, 1fr)) auto;
        gap: 1rem;
        align-items: center;
    }

    .routing-filter-bar input,
    .routing-filter-bar select {
        width: 100%;
        height: 2.5rem;
        margin: 0;
        border: 1px solid var(--border-subtle);
        border-radius: 0.5rem;
        background: var(--bg-surface);
        color: var(--text-primary);
        font-size: 15px;
    }

    .routing-reset-button {
        height: 2.5rem;
        white-space: nowrap;
    }

    .routing-table-wrap {
        overflow-x: auto;
        border: 1px solid var(--border-subtle);
        border-radius: 0.75rem;
        background: var(--bg-surface);
        box-shadow: var(--card-shadow);
    }

    .routing-table {
        width: 100%;
        min-width: 1000px;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }

    .routing-table th:nth-child(1),
    .routing-table td:nth-child(1) {
        width: 22%;
    }

    .routing-table th:nth-child(2),
    .routing-table td:nth-child(2) {
        width: 18%;
    }

    .routing-table th:nth-child(3),
    .routing-table td:nth-child(3) {
        width: 22%;
    }

    .routing-table th:nth-child(4),
    .routing-table td:nth-child(4) {
        width: 12%;
    }

    .routing-table th:nth-child(5),
    .routing-table td:nth-child(5) {
        width: 26%;
    }

    .routing-table th {
        padding: 0.75rem 1rem;
        background: var(--bg-surface);
        color: var(--text-muted);
        font-size: 12px;
        font-weight: 500;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        text-align: left;
        border-bottom: 1px solid var(--border-subtle);
    }

    .routing-table td {
        padding: 1rem;
        color: var(--text-primary);
        vertical-align: middle;
        border-bottom: 1px solid var(--border-subtle);
    }

    .routing-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .routing-table tbody tr:hover td {
        background: rgba(248, 250, 252, 0.6);
    }

    .routing-row-unassigned td {
        background: var(--bg-surface);
    }

    .routing-designation-name,
    .routing-holder-name {
        color: var(--text-primary);
        font-size: 15px;
        font-weight: 800;
        line-height: 1.35;
    }

    .routing-muted {
        margin-top: 4px;
        color: var(--text-muted);
        font-size: 12px;
        line-height: 1.35;
    }

    .routing-holder-unassigned {
        color: var(--status-warning-text);
        font-size: 15px;
        font-weight: 800;
    }

    .routing-scope-stack {
        display: grid;
        gap: 4px;
        justify-items: start;
    }

    .routing-scope-pill {
        display: inline-flex;
        max-width: 160px;
        padding: 7px 13px;
        border-radius: 999px;
        background: var(--status-info-bg);
        color: var(--status-info-text);
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .routing-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.125rem 0.625rem;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    .routing-status-badge.assigned {
        background: var(--status-success-bg);
        color: var(--status-success-text);
    }

    .routing-status-badge.unassigned {
        background: var(--status-warning-bg);
        color: var(--status-warning-text);
    }

    .routing-assignment-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 10px;
        align-items: start;
    }

    .routing-assignment-form button {
        width: 100%;
        min-height: 42px;
        padding-inline: 16px;
        border-radius: 0.5rem;
        white-space: nowrap;
    }

    .routing-no-eligible {
        margin: 0;
        color: var(--status-warning-text);
    }

    .routing-empty-filter-state {
        padding: 18px;
        border-radius: 16px;
        background: var(--bg-surface);
        border: 1px dashed var(--border-subtle);
        color: var(--text-muted);
        text-align: center;
    }

    .routing-row.is-hidden {
        display: none;
    }

    .routing-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1300;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 22px;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(4px);
    }

    .routing-modal-backdrop[hidden] {
        display: none !important;
    }

    .routing-modal {
        width: min(680px, 100%);
        display: grid;
        gap: 18px;
        padding: 24px;
        border-radius: 0.75rem;
        background: var(--bg-surface);
        border: 1px solid var(--border-subtle);
        box-shadow: 0 24px 70px rgba(14, 39, 66, 0.24);
        backdrop-filter: blur(18px);
    }

    .routing-modal-header,
    .routing-modal-actions {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
    }

    .routing-modal-header {
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-subtle);
    }

    .routing-modal-header h2,
    .routing-modal-header p {
        margin: 0;
    }

    .routing-modal-header p {
        margin-top: 6px;
        color: var(--text-muted);
    }

    .routing-modal-actions {
        justify-content: flex-end;
        align-items: center;
    }

    .routing-row-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .routing-row-actions .button {
        min-height: 38px;
        padding: 8px 13px;
    }

    .routing-remove-button {
        color: var(--status-danger-text);
    }

    @media (max-width: 1200px) {
        .routing-filter-bar {
            grid-template-columns: 1fr 1fr;
        }

        .routing-create-panel,
        .routing-create-form {
            grid-template-columns: 1fr 1fr;
        }

        .routing-reset-button {
            width: 100%;
        }
    }

    @media (max-width: 760px) {
        #routing-configuration {
            padding: 16px;
        }

        .routing-create-panel,
        .routing-create-form {
            grid-template-columns: 1fr;
        }

        .routing-page-header h1 {
            font-size: 28px;
        }

        .routing-filter-bar {
            grid-template-columns: 1fr;
        }

        .routing-modal-actions {
            flex-direction: column;
            align-items: stretch;
        }

        .routing-modal-actions button {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const routingSection = document.getElementById('routing-configuration');

    if (!routingSection) {
        return;
    }

    const searchInput = routingSection.querySelector('[data-routing-search]');
    const programSelect = routingSection.querySelector('[data-routing-program]');
    const yearSelect = routingSection.querySelector('[data-routing-year]');
    const statusSelect = routingSection.querySelector('[data-routing-status]');
    const resetButton = routingSection.querySelector('[data-routing-reset]');
    const emptyState = routingSection.querySelector('[data-routing-empty]');
    const rows = Array.from(routingSection.querySelectorAll('[data-routing-row]'));
    const assignmentModal = document.getElementById('routingAssignmentModal');
    const assignmentForm = document.getElementById('routingAssignmentForm');
    const assignmentTitle = document.getElementById('routingAssignmentTitle');
    const assignmentScope = document.getElementById('routingAssignmentScope');
    const assignmentFormKey = document.getElementById('routingAssignmentFormKey');
    const assignmentUserSelect = document.getElementById('routingAssignmentUserSelect');
    const assignmentSearch = document.getElementById('routingAssignmentSearch');
    const assignmentHelp = document.getElementById('routingAssignmentHelp');
    const designationTypeSelect = routingSection.querySelector('[data-designation-type]');
    const designationProgramScope = routingSection.querySelector('[data-designation-program-scope]');
    const designationYearScope = routingSection.querySelector('[data-designation-year-scope]');
    let eligibleController = null;
    let eligibleSearchTimer = null;
    let activeAssignmentButton = null;

    const normalize = function (value) {
        return String(value || '').toLowerCase().trim();
    };

    const applyRoutingFilters = function () {
        const searchValue = normalize(searchInput?.value);
        const programValue = programSelect?.value || '';
        const yearValue = yearSelect?.value || '';
        const statusValue = statusSelect?.value || '';

        let visibleRows = 0;

        rows.forEach(function (row) {
            const matchesSearch = !searchValue || normalize(row.dataset.routingName).includes(searchValue);
            const matchesProgram = !programValue || row.dataset.routingProgram === programValue;
            const matchesYear = !yearValue || row.dataset.routingYear === yearValue;
            const matchesStatus = !statusValue || row.dataset.routingStatus === statusValue;

            const isVisible = matchesSearch && matchesProgram && matchesYear && matchesStatus;

            row.classList.toggle('is-hidden', !isVisible);

            if (isVisible) {
                visibleRows += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = visibleRows !== 0;
        }
    };

    [searchInput, programSelect, yearSelect, statusSelect].forEach(function (control) {
        if (!control) {
            return;
        }

        control.addEventListener('input', applyRoutingFilters);
        control.addEventListener('change', applyRoutingFilters);
    });

    resetButton?.addEventListener('click', function () {
        if (searchInput) searchInput.value = '';
        if (programSelect) programSelect.value = '';
        if (yearSelect) yearSelect.value = '';
        if (statusSelect) statusSelect.value = '';

        applyRoutingFilters();
    });

    applyRoutingFilters();

    const syncDesignationScopeFields = function () {
        const type = designationTypeSelect?.value || '';
        const needsProgram = ['acad_org_treasurer', 'acad_org_adviser'].includes(type);
        const needsYear = type === 'year_level_treasurer';

        if (designationProgramScope) {
            designationProgramScope.hidden = !needsProgram;
            designationProgramScope.querySelector('select').disabled = !needsProgram;
        }

        if (designationYearScope) {
            designationYearScope.hidden = !needsYear;
            designationYearScope.querySelector('select').disabled = !needsYear;
        }
    };

    designationTypeSelect?.addEventListener('change', syncDesignationScopeFields);
    syncDesignationScopeFields();

    routingSection.querySelectorAll('[data-routing-delete-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const name = form.dataset.routingDeleteName || 'this routing office';

            if (!window.confirm('Remove ' + name + ' from new clearance routing? Existing history stays intact.')) {
                event.preventDefault();
            }
        });
    });

    const closeAssignmentModal = function () {
        if (!assignmentModal) {
            return;
        }

        assignmentModal.hidden = true;
        document.body.style.overflow = '';
    };

    const setAssignmentOptions = function (users, currentUserId) {
        assignmentUserSelect.innerHTML = '';

        if (!users.length) {
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = 'No eligible holders found';
            assignmentUserSelect.appendChild(emptyOption);
            assignmentUserSelect.disabled = true;
            assignmentHelp.textContent = 'No eligible holders found for this designation.';
            return;
        }

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select eligible holder';
        assignmentUserSelect.appendChild(placeholder);

        users.forEach(function (user) {
            const option = document.createElement('option');
            option.value = user.id;
            option.textContent = user.label + (user.meta ? ' - ' + user.meta : '');
            option.selected = String(user.id) === String(currentUserId || '');
            assignmentUserSelect.appendChild(option);
        });

        assignmentUserSelect.disabled = false;
        assignmentHelp.textContent = users.length + ' eligible holder' + (users.length === 1 ? '' : 's') + ' loaded.';
    };

    const loadEligibleUsers = async function (button, searchValue = '') {
        if (!assignmentModal || !assignmentForm || !assignmentUserSelect) {
            return;
        }

        if (eligibleController) {
            eligibleController.abort();
        }

        eligibleController = new AbortController();

        const url = new URL(button.dataset.eligibleUrl, window.location.origin);
        if (searchValue.trim()) {
            url.searchParams.set('search', searchValue.trim());
        }

        assignmentUserSelect.innerHTML = '<option value="">Loading eligible holders...</option>';
        assignmentUserSelect.disabled = true;
        assignmentHelp.textContent = searchValue.trim()
            ? 'Searching eligible holders...'
            : 'Loading eligible holders...';

        try {
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                signal: eligibleController.signal,
            });

            if (!response.ok) {
                throw new Error('Unable to load eligible holders.');
            }

            const payload = await response.json();
            setAssignmentOptions(payload.users || [], payload.current_user_id || button.dataset.currentUserId);

            if (payload.requires_search && !searchValue.trim()) {
                assignmentHelp.textContent = payload.users?.length
                    ? 'Current holder is shown. Search by name or student number to change it.'
                    : 'Search by name or student number to find eligible students.';
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            assignmentUserSelect.innerHTML = '<option value="">Unable to load eligible holders</option>';
            assignmentUserSelect.disabled = true;
            assignmentHelp.textContent = 'Refresh the page and try again.';
        }
    };

    const openAssignmentModal = function (button) {
        if (!assignmentModal || !assignmentForm || !assignmentUserSelect) {
            return;
        }

        activeAssignmentButton = button;

        assignmentTitle.textContent = button.dataset.designationName || 'Assign Holder';
        assignmentScope.textContent = button.dataset.designationScope || 'Whole school';
        assignmentForm.action = button.dataset.assignmentAction;
        assignmentFormKey.value = button.dataset.formKey || '';
        assignmentSearch.value = '';
        assignmentModal.hidden = false;
        document.body.style.overflow = 'hidden';
        assignmentSearch.focus();

        loadEligibleUsers(button);
    };

    assignmentSearch?.addEventListener('input', function () {
        if (!activeAssignmentButton) {
            return;
        }

        window.clearTimeout(eligibleSearchTimer);
        eligibleSearchTimer = window.setTimeout(function () {
            loadEligibleUsers(activeAssignmentButton, assignmentSearch.value);
        }, 220);
    });

    routingSection.querySelectorAll('.routing-assign-trigger').forEach(function (button) {
        button.addEventListener('click', function () {
            openAssignmentModal(button);
        });
    });

    document.querySelectorAll('[data-routing-assignment-close]').forEach(function (button) {
        button.addEventListener('click', closeAssignmentModal);
    });

    assignmentModal?.addEventListener('click', function (event) {
        if (event.target === assignmentModal) {
            closeAssignmentModal();
        }
    });
});
</script>
@endpush



