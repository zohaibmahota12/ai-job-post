<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Opportunity Hunter')</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|syne:600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper text-ink antialiased">
        <div class="lg:flex">
            <aside class="hidden min-h-screen w-64 shrink-0 flex-col border-r border-white/5 bg-pine text-sand lg:flex">
                <div class="px-5 py-7">
                    <a href="{{ route('dashboard') }}" class="font-serif text-xl font-bold tracking-tight text-white">Opportunity Hunter</a>
                    <p class="mt-2 text-sm leading-relaxed text-sand/55">Review first. Apply yourself.</p>
                </div>
                @include('partials.nav', ['variant' => 'dark'])
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-20 border-b border-line bg-card/90 backdrop-blur-md lg:hidden">
                    <div class="flex items-center justify-between px-4 py-3.5">
                        <a href="{{ route('dashboard') }}" class="font-serif text-lg font-bold text-pine">Opportunity Hunter</a>
                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded-lg border border-line bg-paper px-3 py-1.5 text-sm font-medium text-ink">Menu</summary>
                            <div class="absolute right-0 z-10 mt-2 w-60 rounded-2xl border border-line bg-card p-2 shadow-lg shadow-ink/10">
                                @include('partials.nav', ['variant' => 'light'])
                            </div>
                        </details>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-6xl px-4 py-7 sm:px-6 lg:px-10 lg:py-9">
                    @if (session('status'))
                        <p class="mb-5 rounded-xl border border-moss/25 bg-moss/8 px-4 py-3 text-sm text-pine">{{ session('status') }}</p>
                    @endif
                    @if (session('error'))
                        <p class="mb-5 rounded-xl border border-clay/25 bg-clay/8 px-4 py-3 text-sm text-clay">{{ session('error') }}</p>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
