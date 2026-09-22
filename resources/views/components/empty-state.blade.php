@props(['title', 'body'])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-line bg-card px-5 py-8']) }}>
    <h2 class="font-serif text-xl text-pine">{{ $title }}</h2>
    <p class="mt-2 max-w-xl text-sm leading-relaxed text-bark">{{ $body }}</p>
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
