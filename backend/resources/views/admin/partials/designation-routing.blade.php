<section id="routing-configuration" class="dashboard-section">
    <div class="section-heading">
        <div>
            <div class="eyebrow">Routing Configuration</div>
            <h2>DESIGNATION ASSIGNMENT</h2>
        </div>
    </div>

    <div class="card">
        <div class="section-subheader">
            <div>
                <h3>Assign Holders</h3>
                <p class="section-copy">Choose who currently handles each designation.</p>
            </div>
        </div>

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
                        $currentOfficeAccount?->designationDisplayName(),
                        $currentOfficeAccount?->program?->code ?? $currentStudentProfile?->program?->code,
                        ($currentOfficeAccount?->year_level ?? $currentStudentProfile?->year_level)
                            ? 'Year ' . ($currentOfficeAccount?->year_level ?? $currentStudentProfile?->year_level)
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

                    <form method="POST" action="{{ route('admin.office-designations.assignment.update', $designation) }}">
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
                                            $eligibleOfficeAccount?->designationDisplayName(),
                                            $eligibleOfficeAccount?->program?->code ?? $eligibleStudentProfile?->program?->code,
                                            ($eligibleOfficeAccount?->year_level ?? $eligibleStudentProfile?->year_level)
                                                ? 'Year ' . ($eligibleOfficeAccount?->year_level ?? $eligibleStudentProfile?->year_level)
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

                        <div class="toolbar" style="margin-top: 14px;">
                            <button type="submit" {{ $eligibleUsers->isEmpty() ? 'disabled' : '' }}>
                                Save Assignment
                            </button>
                        </div>
                    </form>
                </details>
            @empty
                <div class="record">
                    <p class="muted" style="margin: 0;">No active designations yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
