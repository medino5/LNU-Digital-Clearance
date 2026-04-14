@php($validationErrors = $validationErrors ?? collect($errors->getBags())->flatMap(fn ($bag) => $bag->all()))

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
