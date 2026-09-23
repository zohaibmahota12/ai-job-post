@props(['title', 'body'])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-line bg-sand/60 px-6 py-10 text-center']) }}>
    <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>
    <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-bark">{{ $body }}</p>
    @isset($action)
        <div class="mt-5 flex justify-center">{{ $action }}</div>
    @endisset
</div>
