@props([
    'health',
])

@php
    $classes = match ($health->value) {
        'healthy' => 'bg-emerald-50 text-emerald-900 border-emerald-200',
        'warning' => 'bg-amber-50 text-amber-900 border-amber-200',
        'failing' => 'bg-red-50 text-red-900 border-red-200',
        default => 'bg-sand text-bark border-line',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-medium '.$classes]) }}>
    {{ $health->label() }}
</span>
