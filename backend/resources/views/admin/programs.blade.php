@extends('layouts.portal', [
    'title' => 'Programs',
    'subtitle' => 'Manage the official program code, name, and organization label.',
])

@section('page')
    @php
        $validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all());
        $activeFormKey = old('_form_key');
    @endphp

    <div class="admin-page">
        @include('admin.partials.page-feedback')

        <section class="admin-page-header">
            <div>
                <h1>PROGRAMS</h1>
                <p>Manage program codes, names, and organization labels.</p>
            </div>
        </section>

            <div class="grid-2">
                <div class="section-stack">
                    <div class="admin-section-card" id="program-create-card">
                        <div class="eyebrow">Create Program</div>

                        @php($programCreateFormKey = 'program-create')

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
                                        value="{{ $activeFormKey === $programCreateFormKey ? old('code') : '' }}"
                                        required
                                    >
                                    @if($activeFormKey === $programCreateFormKey)
                                        <x-field-error field="code" bag="programCreate" />
                                    @endif
                                </label>

                                <label>
                                    Organization Name
                                    <input
                                        type="text"
                                        name="org_name"
                                        placeholder="DIGITS"
                                        value="{{ $activeFormKey === $programCreateFormKey ? old('org_name') : '' }}"
                                        required
                                    >
                                    @if($activeFormKey === $programCreateFormKey)
                                        <x-field-error field="org_name" bag="programCreate" />
                                    @endif
                                </label>
                            </div>

                            <p class="mini" style="margin-top: -4px;">Letters, numbers, and hyphens only. Saved in uppercase.</p>

                            <label>
                                Program Name
                                <input
                                    type="text"
                                    name="name"
                                    placeholder="Bachelor of Science in Information Technology"
                                    value="{{ $activeFormKey === $programCreateFormKey ? old('name') : '' }}"
                                    required
                                >
                                @if($activeFormKey === $programCreateFormKey)
                                    <x-field-error field="name" bag="programCreate" />
                                @endif
                            </label>

                            <button
                                type="submit"
                                data-loading-button
                                data-loading-text="Saving Program..."
                            >
                                Save Program
                            </button>
                        </form>
                    </div>
                </div>

                <div class="section-stack">
                    <div class="admin-section-card" id="program-records">
                        <div class="eyebrow">Programs</div>

                        <div class="list scrollable-list">
                            @forelse($programs as $program)
                                @php($programUpdateFormKey = 'program-update-' . $program->id)

                                <details class="record" {{ $activeFormKey === $programUpdateFormKey ? 'open' : '' }}>
                                    <summary>{{ $program->code }} - {{ $program->name }}</summary>
                                    <div class="divider"></div>

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
                                                    value="{{ $activeFormKey === $programUpdateFormKey ? old('code', $program->code) : $program->code }}"
                                                    required
                                                >
                                                @if($activeFormKey === $programUpdateFormKey)
                                                    <x-field-error field="code" bag="programUpdate" />
                                                @endif
                                            </label>

                                            <label>
                                                Organization Name
                                                <input
                                                    type="text"
                                                    name="org_name"
                                                    value="{{ $activeFormKey === $programUpdateFormKey ? old('org_name', $program->org_name) : $program->org_name }}"
                                                    required
                                                >
                                                @if($activeFormKey === $programUpdateFormKey)
                                                    <x-field-error field="org_name" bag="programUpdate" />
                                                @endif
                                            </label>
                                        </div>

                                        <p class="mini" style="margin-top: -4px;">Letters, numbers, and hyphens only. Saved in uppercase.</p>

                                        <label>
                                            Program Name
                                            <input
                                                type="text"
                                                name="name"
                                                value="{{ $activeFormKey === $programUpdateFormKey ? old('name', $program->name) : $program->name }}"
                                                required
                                            >
                                            @if($activeFormKey === $programUpdateFormKey)
                                                <x-field-error field="name" bag="programUpdate" />
                                            @endif
                                        </label>

                                        <button
                                            type="submit"
                                            data-loading-button
                                            data-loading-text="Updating Program..."
                                        >
                                            Update Program
                                        </button>
                                    </form>
                                </details>
                            @empty
                                <div class="empty-state">
                                    No programs added yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
