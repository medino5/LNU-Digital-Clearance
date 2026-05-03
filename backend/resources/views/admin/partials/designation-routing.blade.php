<section id="routing-configuration" class="admin-section-card routing-management-card">
    <div class="routing-page-header">
        <div>
            <h1>DESIGNATION ASSIGNMENT</h1>
            <p>Manage designation holders, scopes, statuses, and assignments.</p>
        </div>
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
                            $eligibleUsers = $designation->getRelation('eligible_users');
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
                            ])->filter()->implode(' • ');

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
                                            • {{ $currentHolderMeta }}
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
                                <form
                                    method="POST"
                                    action="{{ route('admin.office-designations.assignment.update', $designation) }}"
                                    data-loading-form
                                    class="routing-assignment-form"
                                >
                                    @csrf
                                    @method('PUT')

                                    <input type="hidden" name="_form_key" value="{{ $designationFormKey }}">

                                    <select name="user_id" required>
                                        <option value="">Select eligible holder</option>

                                        @foreach($eligibleUsers as $eligibleUser)
                                            @php
                                                $eligibleOfficeAccount = $eligibleUser->officeAccount;
                                                $eligibleStudentProfile = $eligibleUser->studentProfile;
                                                $isSelected = $currentUser && $currentUser->id === $eligibleUser->id;

                                                $eligibleName = $eligibleOfficeAccount?->display_name ?? $eligibleUser->formattedName();

                                                $eligibleType = $eligibleOfficeAccount
                                                    ? 'Staff'
                                                    : ($eligibleStudentProfile ? 'Student' : 'User');

                                                $eligibleMeta = collect([
                                                    $eligibleOfficeAccount?->officeTypeLabel(),
                                                    $eligibleOfficeAccount?->scopeSummaryLabel(),
                                                    $eligibleStudentProfile?->program?->code,
                                                    $eligibleStudentProfile?->year_level ? 'Year ' . $eligibleStudentProfile->year_level : null,
                                                ])->filter()->implode(' • ');
                                            @endphp

                                            <option
                                                value="{{ $eligibleUser->id }}"
                                                {{ ($activeFormKey === $designationFormKey ? (string) old('user_id') === (string) $eligibleUser->id : $isSelected) ? 'selected' : '' }}
                                            >
                                                {{ $eligibleType }} — {{ $eligibleName }}{{ $eligibleMeta ? ' — ' . $eligibleMeta : '' }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @if($activeFormKey === $designationFormKey)
                                        <x-field-error field="user_id" bag="designationAssignment" />
                                    @endif

                                    @if($eligibleUsers->isEmpty())
                                        <p class="mini routing-no-eligible">
                                            No eligible holders found.
                                        </p>
                                    @endif

                                    <button
                                        type="submit"
                                        data-loading-button
                                        data-loading-text="Saving..."
                                        {{ $eligibleUsers->isEmpty() ? 'disabled' : '' }}
                                    >
                                        {{ $currentAssignment ? 'Change Holder' : 'Assign Holder' }}
                                    </button>
                                </form>
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
</section>

@push('styles')
<style>
    #routing-configuration {
        display: grid;
        gap: 20px;
        padding: 20px 28px 28px;
        width: 100%;
        max-width: none;
    }

    .routing-page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 18px;
        width: 100%;
        padding: 24px;
        border: 1px solid #e4dacd;
        border-radius: 22px;
        background: #fffdf8;
        box-shadow: 0 12px 28px rgba(24, 58, 99, 0.04);
    }

    .routing-page-header h1 {
        margin: 0;
        color: #173c66;
        font-size: 34px;
        line-height: 1.1;
        letter-spacing: -0.03em;
    }

    .routing-page-header p {
        margin: 8px 0 0;
        color: #5d6b84;
        font-size: 17px;
    }

    .routing-records-card {
        display: grid;
        gap: 18px;
        width: 100%;
        padding: 24px;
        border: 1px solid #e4dacd;
        border-radius: 22px;
        background: #fffdf8;
        box-shadow: 0 12px 28px rgba(24, 58, 99, 0.04);
    }

    .routing-records-header {
        display: grid;
        gap: 16px;
    }

    .routing-filter-bar {
        display: grid;
        grid-template-columns: minmax(240px, 1.4fr) repeat(3, minmax(150px, 1fr)) auto;
        gap: 10px;
        align-items: center;
    }

    .routing-filter-bar input,
    .routing-filter-bar select {
        width: 100%;
        height: 44px;
        margin: 0;
        border: 1px solid #d8cdbc;
        border-radius: 13px;
        background: #ffffff;
        color: #172033;
        font-size: 15px;
    }

    .routing-reset-button {
        height: 44px;
        white-space: nowrap;
    }

    .routing-table-wrap {
        overflow-x: auto;
        border: 1px solid #e4dacd;
        border-radius: 16px;
        background: #ffffff;
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
        padding: 13px 12px;
        background: #f8f4ea;
        color: #667085;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        text-align: left;
        border-bottom: 1px solid #ded3c3;
    }

    .routing-table td {
        padding: 14px 12px;
        color: #183a63;
        vertical-align: middle;
        border-bottom: 1px solid #e7ddcf;
    }

    .routing-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .routing-table tbody tr:hover td {
        background: #fffaf0;
    }

    .routing-row-unassigned td {
        background: #fffdf5;
    }

    .routing-designation-name,
    .routing-holder-name {
        color: #183a63;
        font-size: 15px;
        font-weight: 800;
        line-height: 1.35;
    }

    .routing-muted {
        margin-top: 4px;
        color: #5d6b84;
        font-size: 12px;
        line-height: 1.35;
    }

    .routing-holder-unassigned {
        color: #9a650d;
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
        background: #eef4fb;
        color: #173c66;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .routing-status-badge {
        display: inline-flex;
        align-items: center;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .routing-status-badge.assigned {
        background: #e2f4ea;
        color: #1f7a4f;
    }

    .routing-status-badge.unassigned {
        background: #fff0cc;
        color: #9a650d;
    }

    .routing-assignment-form {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 8px;
        align-items: start;
    }

    .routing-assignment-form select {
        width: 100%;
        height: 42px;
        margin: 0;
        border: 1px solid #d8cdbc;
        border-radius: 13px;
        background: #ffffff;
        color: #172033;
        font-size: 14px;
    }

    .routing-assignment-form button {
        width: 100%;
        min-height: 42px;
        padding-inline: 16px;
        border-radius: 999px;
        white-space: nowrap;
    }

    .routing-no-eligible {
        margin: 0;
        color: #9a650d;
    }

    .routing-empty-filter-state {
        padding: 18px;
        border-radius: 16px;
        background: #ffffff;
        border: 1px dashed #d5cbbd;
        color: #667085;
        text-align: center;
    }

    .routing-row.is-hidden {
        display: none;
    }

    @media (max-width: 1200px) {
        .routing-filter-bar {
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

        .routing-page-header,
        .routing-records-card {
            padding: 18px;
        }

        .routing-page-header h1 {
            font-size: 28px;
        }

        .routing-filter-bar {
            grid-template-columns: 1fr;
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
});
</script>
@endpush