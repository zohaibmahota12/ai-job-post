@props(['variant' => 'primary'])

@php
    $classes = match ($variant) {
        'secondary' => 'inline-flex items-center justify-center rounded-lg border border-line bg-card px-4 py-2.5 text-sm font-medium text-ink transition hover:bg-sand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moss',
        'danger' => 'inline-flex items-center justify-center rounded-lg bg-clay px-4 py-2.5 text-sm font-medium text-white transition hover:bg-clay/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-clay',
        default => 'inline-flex items-center justify-center rounded-lg bg-moss px-4 py-2.5 text-sm font-medium text-white transition hover:bg-moss/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-moss',
    };
@endphp

<button {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
