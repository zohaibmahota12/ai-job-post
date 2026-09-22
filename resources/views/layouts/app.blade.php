<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Opportunity Hunter')</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:500,620,700|outfit:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper text-ink antialiased">
        <div class="lg:flex">
            <aside class="hidden min-h-screen w-64 shrink-0 flex-col bg-pine text-sand lg:flex">
                <div class="px-5 py-6">
                    <a href="{{ route('dashboard') }}" class="font-serif text-2xl text-white">Opportunity Hunter</a>
                    <p class="mt-2 text-sm text-sand/80">Review first. Apply yourself.</p>
                </div>
                @include('partials.nav', ['variant' => 'dark'])
            </aside>

            <div class="min-w-0 flex-1">
                <header class="border-b border-line bg-card lg:hidden">
                    <div class="flex items-center justify-between px-4 py-4">
                        <a href="{{ route('dashboard') }}" class="font-serif text-xl text-pine">Opportunity Hunter</a>
                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded-md border border-line px-3 py-1 text-sm">Menu</summary>
                            <div class="absolute right-0 z-10 mt-2 w-56 rounded-xl border border-line bg-card p-2 shadow-lg">
                                @include('partials.nav', ['variant' => 'light'])
                            </div>
                        </details>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-10 lg:py-8">
                    @if (session('status'))
                        <p class="mb-4 rounded-md border border-moss/30 bg-moss/10 px-3 py-2 text-sm text-pine">{{ session('status') }}</p>
                    @endif
                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
