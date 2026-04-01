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
                <h3>Manage Designation Assignments</h3>
                <p class="section-copy">Review each routing designation and choose which eligible office account currently holds it.</p>
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
                @endphp

                <details class="record" {{ $activeFormKey === $designationFormKey ? 'open' : '' }}>
                    <summary>
                        {{ $designation->display_name }}
                        <span class="mini" style="display:block; margin-top:6px;">
                            Current Assigned:
                            {{ $currentOfficeAccount?->display_name ?? $currentUser?->name ?? 'No active assignment' }}
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
                            <p style="margin-top: 6px;">{{ $designation->scopeLabel() ?? 'No scope restriction' }}</p>
                        </div>

                        <div>
                            <div class="eyebrow">Current Assigned Office User</div>
                            <p style="margin-top: 6px;">
                                {{ $currentOfficeAccount?->display_name ?? $currentUser?->name ?? 'No active assignment' }}
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
                            Select Office User
                            <select name="user_id" required>
                                <option value="">Select office user</option>
                                @foreach($eligibleUsers as $eligibleUser)
                                    @php
                                        $eligibleOfficeAccount = $eligibleUser->officeAccount;
                                        $isSelected = $currentUser && $currentUser->id === $eligibleUser->id;
                                        $labelParts = collect([
                                            $eligibleOfficeAccount?->display_name ?? $eligibleUser->name,
                                            $eligibleOfficeAccount?->program?->code,
                                            $eligibleOfficeAccount?->year_level ? 'Year ' . $eligibleOfficeAccount->year_level : null,
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
                                No eligible office users found for this designation scope.
                            </p>
                        @endif

                        <div class="toolbar" style="margin-top: 14px;">
                            {{-- Added for MAE-XX: loading indicator + duplicate-submit protection --}}
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
                <div class="record">
                    <p class="muted" style="margin: 0;">No active designations are available.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
