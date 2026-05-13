@extends('layouts.portal', [
    'title' => 'Programs',
    'subtitle' => 'Manage the official program code, name, and organization label.',
])

@section('page')
    @php
        $activeFormKey = old('_form_key');
        $programCreateFormKey = 'program-create';
        $shouldOpenCreate = $activeFormKey === $programCreateFormKey;
    @endphp

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>Programs</h1>
                <p>Manage program codes, names, and organization labels.</p>
            </div>

            <button
                type="button"
                class="management-primary-action"
                data-modal-open="program-create-card"
            >
                Add Program
            </button>
        </section>

        <section class="admin-section-card management-card" id="program-records">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Program Records</div>
                </div>
            </div>

            <div class="management-table-wrap">
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Program Name</th>
                            <th>Organization</th>
                            <th>Students</th>
                            <th class="management-action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programs as $program)
                            @php
                                $programUpdateFormKey = 'program-update-' . $program->id;
                            @endphp

                            <tr>
                                <td>
                                    <strong class="program-code">{{ $program->code }}</strong>
                                </td>
                                <td>
                                    <div class="table-main-text">{{ $program->name }}</div>
                                </td>
                                <td>
                                    <span class="org-pill">{{ $program->org_name }}</span>
                                </td>
                                <td>{{ number_format($program->students_count) }}</td>
                                <td>
                                    <div class="table-action-stack">
                                        <button
                                            type="button"
                                            class="button secondary table-action-button"
                                            data-modal-open="program-edit-{{ $program->id }}"
                                        >
                                            Edit
                                        </button>

                                        @if($program->students_count === 0)
                                            <button
                                                type="button"
                                                class="button warn table-action-button"
                                                data-modal-open="program-delete-{{ $program->id }}"
                                            >
                                                Delete
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">No programs added yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- CREATE MODAL --}}
        <div
            class="management-modal {{ $shouldOpenCreate ? 'is-open' : '' }}"
            id="program-create-card"
            data-modal
        >
            <div class="management-modal-panel">
                <div class="management-modal-header">
                    <div>
                        <div class="eyebrow">Create Program</div>
                        <h2>Add Program</h2>
                    </div>

                    <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.programs.store') }}">
                    @csrf
                    <input type="hidden" name="_form_key" value="{{ $programCreateFormKey }}">

                    <div class="field-grid">
                        <label>
                            Program Code
                            <input
                                type="text"
                                name="code"
                                placeholder="BSIT"
                                value="{{ $shouldOpenCreate ? old('code') : '' }}"
                                maxlength="5"
                                required
                            >
                            @if($shouldOpenCreate)
                                <x-field-error field="code" bag="programCreate" />
                            @endif
                        </label>

                        <label>
                            Organization Name
                            <input
                                type="text"
                                name="org_name"
                                placeholder="DIGITS"
                                value="{{ $shouldOpenCreate ? old('org_name') : '' }}"
                                maxlength="100"
                                required
                            >
                            @if($shouldOpenCreate)
                                <x-field-error field="org_name" bag="programCreate" />
                            @endif
                        </label>
                    </div>

                    <p class="mini">Letters, numbers, and hyphens only. Saved in uppercase.</p>

                    <label>
                        Program Name
                        <input
                            type="text"
                            name="name"
                            placeholder="Bachelor of Science in Information Technology"
                            value="{{ $shouldOpenCreate ? old('name') : '' }}"
                            maxlength="120"
                            required
                        >
                        @if($shouldOpenCreate)
                            <x-field-error field="name" bag="programCreate" />
                        @endif
                    </label>

                    <div class="form-actions modal-actions">
                        <button type="button" class="secondary" data-modal-close>Cancel</button>
                        <button
                            type="submit"
                            data-loading-button
                            data-loading-text="Saving Program..."
                        >
                            Save Program
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- EDIT MODALS --}}
        @foreach($programs as $program)
            @php
                $programUpdateFormKey = 'program-update-' . $program->id;
                $shouldOpenEdit = $activeFormKey === $programUpdateFormKey;
            @endphp

            <div
                class="management-modal {{ $shouldOpenEdit ? 'is-open' : '' }}"
                id="program-edit-{{ $program->id }}"
                data-modal
            >
                <div class="management-modal-panel">
                    <div class="management-modal-header">
                        <div>
                            <div class="eyebrow">Edit Program</div>
                            <h2>{{ $program->code }}</h2>
                        </div>

                        <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.programs.update', $program) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form_key" value="{{ $programUpdateFormKey }}">

                        <div class="field-grid">
                            <label>
                                Program Code
                                <input
                                    type="text"
                                    name="code"
                                    value="{{ $shouldOpenEdit ? old('code', $program->code) : $program->code }}"
                                    maxlength="15"
                                    required
                                >
                                @if($shouldOpenEdit)
                                    <x-field-error field="code" bag="programUpdate" />
                                @endif
                            </label>

                            <label>
                                Organization Name
                                <input
                                    type="text"
                                    name="org_name"
                                    value="{{ $shouldOpenEdit ? old('org_name', $program->org_name) : $program->org_name }}"
                                    maxlength="100"
                                    required
                                >
                                @if($shouldOpenEdit)
                                    <x-field-error field="org_name" bag="programUpdate" />
                                @endif
                            </label>
                        </div>

                        <p class="mini">Letters, numbers, and hyphens only. Saved in uppercase.</p>

                        <label>
                            Program Name
                            <input
                            type="text"
                            name="name"
                            value="{{ $shouldOpenEdit ? old('name', $program->name) : $program->name }}"
                            maxlength="100"
                            required
                        >
                            @if($shouldOpenEdit)
                                <x-field-error field="name" bag="programUpdate" />
                            @endif
                        </label>

                        <div class="form-actions modal-actions">
                            <button type="button" class="secondary" data-modal-close>Cancel</button>
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Updating Program..."
                            >
                                Update Program
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($program->students_count === 0)
                <div
                    class="management-modal"
                    id="program-delete-{{ $program->id }}"
                    data-modal
                >
                    <div class="management-modal-panel">
                        <div class="management-modal-header">
                            <div>
                                <div class="eyebrow">Delete Program</div>
                                <h2>{{ $program->code }}</h2>
                            </div>

                            <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                        </div>

                        <form method="POST" action="{{ route('admin.programs.destroy', $program) }}">
                            @csrf
                            @method('DELETE')

                            <p class="callout error">
                                This permanently deletes the program record. Type
                                <strong>DELETE {{ $program->code }}</strong> to confirm.
                            </p>

                            <label>
                                Confirmation
                                <input
                                    type="text"
                                    name="delete_confirmation"
                                    autocomplete="off"
                                    required
                                >
                                <x-field-error field="delete_confirmation" bag="programDelete" />
                            </label>

                            <div class="form-actions modal-actions">
                                <button type="button" class="secondary" data-modal-close>Cancel</button>
                                <button
                                    type="submit"
                                    class="warn"
                                    data-loading-text="Deleting Program..."
                                >
                                    Delete Program
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @include('admin.partials.management-page-styles')
@endsection



