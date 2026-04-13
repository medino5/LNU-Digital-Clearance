@extends('layouts.portal', ['title' => 'Programs'])

@section('page')
    @php
        $validationErrors = collect($errors->getBags())->flatMap(fn ($bag) => $bag->all());
        $activeFormKey = old('_form_key');
    @endphp

    <div class="stack">
        @if(session('success'))
            <div class="callout success">{{ session('success') }}</div>
        @endif

        @if(session('info'))
            <div class="callout success">{{ session('info') }}</div>
        @endif

        @if(session('error'))
            <div class="callout error">{{ session('error') }}</div>
        @endif

        @if($validationErrors->isNotEmpty())
            <div class="callout error">{{ $validationErrors->first() }}</div>
        @endif

        <section class="dashboard-section" style="margin-top: 0; padding-top: 0; border-top: 0;">
            <div class="section-heading">
                <div>
                    <div class="eyebrow">Academic Configuration</div>
                    <h2>PROGRAMS</h2>
                    <p class="section-copy">Manage the official program code, name, and organization label used across routing and records.</p>
                </div>
            </div>

            <div class="grid-2" style="align-items: stretch;">
                <div class="section-stack">
                    <div class="card" style="height: 100%;">
                        <div class="eyebrow">Create Program</div>
                        <h3>Add a program</h3>
                        <p class="section-copy compact-copy">Add the official program code, name, and organization label.</p>

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
                    <div class="card" style="height: 100%;">
                        <div class="eyebrow">Programs</div>
                        <h3>Current programs</h3>

                        <div class="list scrollable-list" style="max-height: 420px;">
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
                                <div class="record">
                                    <p class="muted" style="margin: 0;">No programs added yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection