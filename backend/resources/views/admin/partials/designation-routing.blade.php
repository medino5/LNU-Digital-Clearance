<section id="routing-configuration" class="admin-section-card">
    <div class="routing-header">
        <h1>DESIGNATION ASSIGNMENT</h1>
        <h2>Assign Holders</h2>
        <p class="section-copy compact-copy">Assign current holders to active designations.</p>
    </div>

    <div>

        <div class="list routing-list">
            @forelse($designations as $designation)
                @php
                    $designationFormKey = 'designation-assignment-' . $designation->id;
                    $currentAssignment = $designation->getRelation('current_assignment');
                    $eligibleUsers = $designation->getRelation('eligible_users');
                    $currentUser = $currentAssignment?->user;
                    $currentOfficeAccount = $currentUser?->officeAccount;
                    $currentStudentProfile = $currentUser?->studentProfile;
                    $currentHolderLabel = collect([
                        $currentOfficeAccount?->display_name ?? $currentUser?->formattedName(),
                        $currentOfficeAccount ? 'Office Account' : ($currentStudentProfile ? 'Student Holder' : null),
                        $currentOfficeAccount?->officeTypeLabel(),
                        $currentOfficeAccount?->scopeSummaryLabel(),
                        $currentStudentProfile?->program?->code,
                        $currentStudentProfile?->year_level
                            ? 'Year ' . $currentStudentProfile?->year_level
                            : null,
                    ])->filter()->implode(' | ');
                @endphp

                <details class="record" {{ $activeFormKey === $designationFormKey ? 'open' : '' }}>
                    <summary>
                        {{ $designation->display_name }}
                        <span class="mini" style="display:block; margin-top:6px;">
                            Current Holder:
                            {{ $currentHolderLabel ?: 'No current holder' }}
                            @if($designation->scopeLabel())
                                | Scope: {{ $designation->scopeLabel() }}
                            @endif
                            | Status:
                            @if($currentAssignment)
                                Assigned
                            @else
                                Unassigned
                            @endif
                        </span>
                    </summary>

                    <div class="divider"></div>

                    <div class="field-grid">
                        <div>
                            <div class="eyebrow">Designation Name</div>
                            <p style="margin-top: 6px;">{{ $designation->display_name }}</p>
                        </div>

                        <div>
                            <div class="eyebrow">Office Type</div>
                            <p style="margin-top: 6px;">{{ $designation->officeTypeLabel() }}</p>
                        </div>

                        <div>
                            <div class="eyebrow">Scope</div>
                            <p style="margin-top: 6px;">{{ $designation->scopeLabel() ?? 'Whole school' }}</p>
                        </div>

                        <div>
                            <div class="eyebrow">Current Holder</div>
                            <p style="margin-top: 6px;">
                                {{ $currentHolderLabel ?: 'No current holder' }}
                            </p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <form
                        method="POST"
                        action="{{ route('admin.office-designations.assignment.update', $designation) }}"
                        data-loading-form
                    >
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form_key" value="{{ $designationFormKey }}">

                        <label>
                            Select Holder
                            <select name="user_id" required>
                                <option value="">Select holder</option>
                                @foreach($eligibleUsers as $eligibleUser)
                                    @php
                                        $eligibleOfficeAccount = $eligibleUser->officeAccount;
                                        $eligibleStudentProfile = $eligibleUser->studentProfile;
                                        $isSelected = $currentUser && $currentUser->id === $eligibleUser->id;
                                        $labelParts = collect([
                                            $eligibleOfficeAccount?->display_name ?? $eligibleUser->formattedName(),
                                            $eligibleOfficeAccount ? 'Office Account' : ($eligibleStudentProfile ? 'Student Holder' : null),
                                            $eligibleOfficeAccount?->officeTypeLabel(),
                                            $eligibleOfficeAccount?->scopeSummaryLabel(),
                                            $eligibleStudentProfile?->program?->code,
                                            $eligibleStudentProfile?->year_level
                                                ? 'Year ' . $eligibleStudentProfile?->year_level
                                                : null,
                                        ])->filter()->implode(' | ');
                                    @endphp
                                    <option
                                        value="{{ $eligibleUser->id }}"
                                        {{ ($activeFormKey === $designationFormKey ? (string) old('user_id') === (string) $eligibleUser->id : $isSelected) ? 'selected' : '' }}
                                    >
                                        {{ $labelParts }}
                                    </option>
                                @endforeach
                            </select>
                            @if($activeFormKey === $designationFormKey)
                                <x-field-error field="user_id" bag="designationAssignment" />
                            @endif
                        </label>

                        @if($eligibleUsers->isEmpty())
                            <p class="mini" style="margin-top: 10px; color: #b5442c;">
                                No eligible holders found for this designation scope.
                            </p>
                        @endif

                        <div class="form-actions">
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Saving Assignment..."
                                {{ $eligibleUsers->isEmpty() ? 'disabled' : '' }}
                            >
                                Save Assignment
                            </button>
                        </div>
                    </form>
                </details>
            @empty
                <div class="empty-state">
                    No active designations yet.
                </div>
            @endforelse
        </div>
    </div>
</section>

@push('styles')
<style>
    #routing-configuration {
        display: grid;
        gap: 20px;
    }

    #routing-configuration {
        padding: 22px;
    }

    #routing-configuration .list {
        gap: 14px;
    }

    #routing-configuration .compact-copy {
        margin-top: 0;
        margin-bottom: 0;
        max-width: 60ch;
    }

    #routing-configuration .routing-list {
        max-height: 560px;
    }

    #routing-configuration details.record summary {
        line-height: 1.4;
    }

    #routing-configuration .form-actions {
        margin-top: 14px;
    }

    #routing-configuration .routing-header {
        margin-top: 0;
        margin-bottom: 4px;
    }

    #routing-configuration .routing-header h2 {
        margin: 0 0 8px;
    }

    #routing-configuration .routing-header .section-copy {
        margin: 0;
    }

    #routing-configuration .routing-header .section-copy + .section-copy {
        margin-top: 6px;
    }
</style>
@endpush
