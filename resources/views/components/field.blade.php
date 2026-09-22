@props(['label', 'name', 'type' => 'text', 'id' => null])

@php
    $fieldId = $id ?? $name;
@endphp

<div>
    <label for="{{ $fieldId }}" class="block text-sm font-medium text-bark">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'mt-1 min-h-32 w-full rounded-md border border-line bg-white px-3 py-2 text-ink']) }}>{{ $slot }}</textarea>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" {{ $attributes->merge(['class' => 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-ink']) }}>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-clay">{{ $message }}</p>
    @enderror
</div>
