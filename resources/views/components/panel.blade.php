@props(['title' => null])

<section {{ $attributes->merge(['class' => 'rounded-2xl border border-line bg-card p-5 shadow-sm shadow-ink/5']) }}>
    @if ($title)
        <h2 class="text-sm font-semibold tracking-wide text-ink uppercase">{{ $title }}</h2>
    @endif
    <div @class(['mt-4' => $title])>{{ $slot }}</div>
</section>
