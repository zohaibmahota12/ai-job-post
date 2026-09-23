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
        <div class="relative min-h-screen overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_rgba(13,148,136,0.12),_transparent_50%),radial-gradient(ellipse_at_bottom_left,_rgba(11,31,42,0.06),_transparent_45%)]"></div>
            <div class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-8 sm:px-6 lg:flex-row lg:items-center lg:gap-16 lg:px-8">
                <div class="mb-10 animate-rise lg:mb-0 lg:w-1/2">
                    <a href="{{ route('home') }}" class="font-serif text-2xl font-bold tracking-tight text-pine">Opportunity Hunter</a>
                    <p class="mt-5 max-w-md text-base leading-relaxed text-bark">Find freelance projects and remote roles that fit the skills you actually have. You review every match. You apply yourself.</p>
                </div>
                <main class="w-full animate-rise-delay lg:w-1/2">
                    <div class="rounded-2xl border border-line bg-card p-6 shadow-lg shadow-ink/5 sm:p-8">
                        @if (session('status'))
                            <p class="mb-4 rounded-xl bg-sand px-3 py-2 text-sm text-pine">{{ session('status') }}</p>
                        @endif
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
