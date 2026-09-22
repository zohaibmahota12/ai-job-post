@props(['title' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-line bg-card p-5']) }}>
    @if ($title)
        <h2 class="font-serif text-xl text-pine">{{ $title }}</h2>
    @endif
    <div @class(['mt-3' => $title])>{{ $slot }}</div>
</section>
