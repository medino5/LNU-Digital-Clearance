@props([
    'field',
    'bag' => 'default',
])

@error($field, $bag)
    <div {{ $attributes->merge(['class' => 'field-error']) }}>{{ $message }}</div>
@enderror
