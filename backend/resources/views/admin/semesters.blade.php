@extends('layouts.portal', [
    'title' => 'Semesters',
    'subtitle' => 'Manage active and historical clearance periods.',
])

@section('page')
    @php($validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))
    @php($activeFormKey = old('_form_key'))

    <div class="stack">
        @include('admin.partials.page-feedback')

        <section class="dashboard-section" style="margin-top: 0; padding-top: 0; border-top: 0;">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Academic Configuration</div>
                    <h2>Semesters</h2>
                    <p class="section-copy">Keep the active semester and academic year ready before students start clearances.</p>
                </div>
            </div>

            <div class="grid-2">
                <div class="section-stack">
                    <div class="card">
                        <div class="eyebrow">Create Semester</div>
                        <h3>Add or change the current semester</h3>
                        <p class="section-copy compact-copy">Only one semester should remain active at a time.</p>
                        @php($semesterCreateFormKey = 'semester-create')
                        <form method="POST" action="{{ route('admin.semesters.store') }}">
                            @csrf
                            <input type="hidden" name="_form_key" value="{{ $semesterCreateFormKey }}">
                            <label>
                                Semester Label
                                <input type="text" name="label" placeholder="2nd Semester 2024-2025" value="{{ $activeFormKey === $semesterCreateFormKey ? old('label') : '' }}" required>
                                @if($activeFormKey === $semesterCreateFormKey)
                                    <x-field-error field="label" bag="semesterCreate" />
                                @endif
                            </label>
                            <label>
                                Academic Year
                                <input type="text" name="academic_year" placeholder="2024-2025" value="{{ $activeFormKey === $semesterCreateFormKey ? old('academic_year') : '' }}" required>
                                @if($activeFormKey === $semesterCreateFormKey)
                                    <x-field-error field="academic_year" bag="semesterCreate" />
                                @endif
                            </label>
                            <label class="inline-check">
                                <input type="checkbox" name="is_active" value="1" {{ $activeFormKey === $semesterCreateFormKey && old('is_active') ? 'checked' : '' }}>
                                Set as the active semester
                            </label>
                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Saving Semester..."
                            >
                                Save Semester
                            </button>
                        </form>
                    </div>
                </div>

                <div class="section-stack">
                    <div class="card">
                        <div class="eyebrow">Semester List</div>
                        <h3>Current and past semesters</h3>
                        <div class="list">
                            @foreach($semesters as $semester)
                                @php($semesterUpdateFormKey = 'semester-update-' . $semester->id)
                                <details class="record" {{ $activeFormKey === $semesterUpdateFormKey ? 'open' : '' }}>
                                    <summary>
                                        {{ $semester->label }}
                                        @if($semester->is_active)
                                            <span class="badge active" style="margin-left: 10px;">Active</span>
                                        @endif
                                    </summary>
                                    <div class="divider"></div>
                                    <form method="POST" action="{{ route('admin.semesters.update', $semester) }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="_form_key" value="{{ $semesterUpdateFormKey }}">
                                        <label>
                                            Semester Label
                                            <input type="text" name="label" value="{{ $activeFormKey === $semesterUpdateFormKey ? old('label', $semester->label) : $semester->label }}" required>
                                            @if($activeFormKey === $semesterUpdateFormKey)
                                                <x-field-error field="label" bag="semesterUpdate" />
                                            @endif
                                        </label>
                                        <label>
                                            Academic Year
                                            <input type="text" name="academic_year" value="{{ $activeFormKey === $semesterUpdateFormKey ? old('academic_year', $semester->displayAcademicYear()) : $semester->displayAcademicYear() }}" required>
                                            @if($activeFormKey === $semesterUpdateFormKey)
                                                <x-field-error field="academic_year" bag="semesterUpdate" />
                                            @endif
                                        </label>
                                        <label class="inline-check">
                                            <input
                                                type="checkbox"
                                                name="is_active"
                                                value="1"
                                                {{ $activeFormKey === $semesterUpdateFormKey ? (old('is_active') ? 'checked' : '') : ($semester->is_active ? 'checked' : '') }}
                                            >
                                            Keep this semester active
                                        </label>
                                        <button
                                            type="submit"
                                            data-loading-button
                                            data-loading-text="Updating Semester..."
                                        >
                                            Update Semester
                                        </button>
                                    </form>
                                </details>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
