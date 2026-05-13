@php
    $validationErrors = $validationErrors 
        ?? collect($errors->getBags())->flatMap(fn ($bag) => $bag->all());
@endphp

{{-- SUCCESS --}}
@if(session('success'))
    <div class="callout success" role="alert">
        {{ session('success') }}
    </div>
@endif

{{-- INFO --}}
@if(session('info'))
    <div class="callout success" role="alert">
        {{ session('info') }}
    </div>
@endif

{{-- ERROR --}}
@if(session('error'))
    <div class="callout error" role="alert">
        {{ session('error') }}
    </div>
@endif

{{-- VALIDATION ERRORS --}}
@if($validationErrors->isNotEmpty())
    <div class="callout error">
        <strong>Please check the following:</strong>
        <ul>
            @foreach($validationErrors as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif



