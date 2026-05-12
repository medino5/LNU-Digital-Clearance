@extends('layouts.portal', [
    'title' => 'Semesters',
    'subtitle' => 'Manage active and historical clearance periods.',
])

@section('page')
    @php
        $activeFormKey = old('_form_key');
        $semesterCreateFormKey = 'semester-create';
        $shouldOpenCreate = $activeFormKey === $semesterCreateFormKey;
    @endphp

    <div class="admin-page management-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header management-header">
            <div>
                <h1>SEMESTERS</h1>
                <p>Manage active and historical clearance periods.</p>
            </div>

            <button
                type="button"
                class="management-primary-action"
                data-modal-open="semester-create-card"
            >
                Add Semester
            </button>
        </section>

        <section class="admin-section-card management-card" id="semester-records">
            <div class="management-card-header">
                <div>
                    <div class="eyebrow">Semester Records</div>
                </div>
            </div>

            <div class="management-table-wrap">
                <table class="management-table">
                    <thead>
                        <tr>
                            <th>Semester Label</th>
                            <th>Academic Year</th>
                            <th>Status</th>
                            <th class="management-action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semesters as $semester)
                            <tr class="{{ $semester->is_active ? 'active-semester-row' : '' }}">
                                <td>
                                    <div class="table-main-text">{{ $semester->label }}</div>
                                </td>
                                <td>{{ $semester->displayAcademicYear() }}</td>
                                <td>
                                    @if($semester->is_active)
                                        <span class="badge active">Active Semester</span>
                                    @else
                                        <span class="muted">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="table-action-group">
                                        <button
                                            type="button"
                                            class="button secondary table-action-button"
                                            data-modal-open="semester-edit-{{ $semester->id }}"
                                        >
                                            Edit
                                        </button>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.semesters.destroy', $semester) }}"
                                            onsubmit="return confirm('Delete this semester? This is only allowed if it has no clearance records and is not the active semester.');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="button danger table-action-button"
                                                data-loading-button
                                                data-loading-text="Deleting..."
                                            >
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="empty-state">No semesters added yet.</div>
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
            id="semester-create-card"
            data-modal
        >
            <div class="management-modal-panel">
                <div class="management-modal-header">
                    <div>
                        <div class="eyebrow">Create Semester</div>
                        <h2>Add Semester</h2>
                    </div>

                    <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                </div>

                <form method="POST" action="{{ route('admin.semesters.store') }}">
                    @csrf
                    <input type="hidden" name="_form_key" value="{{ $semesterCreateFormKey }}">

                    <label>
                        Semester Label
                        <input
                            type="text"
                            name="label"
                            placeholder="2nd Semester 2024-2025"
                            value="{{ $shouldOpenCreate ? old('label') : '' }}"
                            maxlength="80"
                            required
                        >
                        @if($shouldOpenCreate)
                            <x-field-error field="label" bag="semesterCreate" />
                        @endif
                    </label>

                    <label>
                        Academic Year
                        <input
                            type="text"
                            name="academic_year"
                            placeholder="2024-2025"
                            value="{{ $shouldOpenCreate ? old('academic_year') : '' }}"
                            maxlength="9"
                            required
                        >
                        @if($shouldOpenCreate)
                            <x-field-error field="academic_year" bag="semesterCreate" />
                        @endif
                    </label>

                    <label class="inline-check">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            {{ $shouldOpenCreate && old('is_active') ? 'checked' : '' }}
                        >
                        Set as the active semester
                    </label>

                    <div class="form-actions modal-actions">
                        <button type="button" class="secondary" data-modal-close>Cancel</button>
                        <button
                            type="submit"
                            data-loading-button
                            data-loading-text="Saving Semester..."
                        >
                            Save Semester
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- EDIT MODALS --}}
        @foreach($semesters as $semester)
            @php
                $semesterUpdateFormKey = 'semester-update-' . $semester->id;
                $shouldOpenEdit = $activeFormKey === $semesterUpdateFormKey;
            @endphp

            <div
                class="management-modal {{ $shouldOpenEdit ? 'is-open' : '' }}"
                id="semester-edit-{{ $semester->id }}"
                data-modal
            >
                <div class="management-modal-panel">
                    <div class="management-modal-header">
                        <div>
                            <div class="eyebrow">Edit Semester</div>
                            <h2>{{ $semester->label }}</h2>
                        </div>

                        <button type="button" class="modal-close-button" data-modal-close>&times;</button>
                    </div>

                    <form method="POST" action="{{ route('admin.semesters.update', $semester) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form_key" value="{{ $semesterUpdateFormKey }}">

                        <label>
                            Semester Label
                            <input
                                type="text"
                                name="label"
                                value="{{ $shouldOpenEdit ? old('label', $semester->label) : $semester->label }}"
                                maxlength="80"
                                required
                            >
                            @if($shouldOpenEdit)
                                <x-field-error field="label" bag="semesterUpdate" />
                            @endif
                        </label>

                        <label>
                            Academic Year
                            <input
                                type="text"
                                name="academic_year"
                                value="{{ $shouldOpenEdit ? old('academic_year', $semester->displayAcademicYear()) : $semester->displayAcademicYear() }}"
                                maxlength="9"
                                required
                            >
                            @if($shouldOpenEdit)
                                <x-field-error field="academic_year" bag="semesterUpdate" />
                            @endif
                        </label>

                        <label class="inline-check">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                {{ $shouldOpenEdit ? (old('is_active') ? 'checked' : '') : ($semester->is_active ? 'checked' : '') }}
                            >
                            Keep this semester active
                        </label>

                        <div class="form-actions modal-actions">
                            <button type="button" class="secondary" data-modal-close>Cancel</button>
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Updating Semester..."
                            >
                                Update Semester
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    @include('admin.partials.management-page-styles')
@endsection
