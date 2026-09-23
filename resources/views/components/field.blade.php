@props(['label', 'name', 'type' => 'text', 'id' => null])

@php
    $fieldId = $id ?? $name;
    $inputClass = 'mt-1.5 w-full rounded-lg border border-line bg-white px-3 py-2.5 text-sm text-ink shadow-sm shadow-ink/5 outline-none transition placeholder:text-bark/70 focus:border-moss focus:ring-2 focus:ring-moss/20';
@endphp

<div>
    <label for="{{ $fieldId }}" class="block text-sm font-medium text-ink">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $fieldId }}" name="{{ $name }}" {{ $attributes->merge(['class' => $inputClass.' min-h-32']) }}>{{ $slot }}</textarea>
    @else
        <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" {{ $attributes->merge(['class' => $inputClass]) }}>
    @endif
    @error($name)
        <p class="mt-1.5 text-sm text-clay">{{ $message }}</p>
    @enderror
</div>
