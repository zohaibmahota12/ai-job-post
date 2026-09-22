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
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-4 py-6 sm:px-6 lg:flex-row lg:items-center lg:gap-16 lg:px-8">
            <div class="mb-8 lg:mb-0 lg:w-1/2">
                <a href="{{ route('home') }}" class="font-serif text-2xl text-pine">Opportunity Hunter</a>
                <p class="mt-4 max-w-md text-lg leading-relaxed text-bark">Find freelance projects and remote roles that fit the skills you actually have. You review every match. You apply yourself.</p>
            </div>
            <main class="w-full lg:w-1/2">
                <div class="rounded-2xl border border-line bg-card p-6 shadow-sm sm:p-8">
                    @if (session('status'))
                        <p class="mb-4 rounded-md bg-sand px-3 py-2 text-sm text-pine">{{ session('status') }}</p>
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
    </body>
</html>
