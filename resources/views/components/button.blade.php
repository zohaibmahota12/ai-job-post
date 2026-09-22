@props(['variant' => 'primary'])

@php
    $classes = $variant === 'secondary'
        ? 'inline-flex items-center justify-center rounded-md border border-line bg-white px-4 py-2 text-sm font-medium text-ink hover:bg-sand'
        : 'inline-flex items-center justify-center rounded-md bg-clay px-4 py-2 text-sm font-medium text-white hover:bg-clay/90';
@endphp

<button {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
