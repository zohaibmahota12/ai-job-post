<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Opportunity Hunter</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:500,620,700|outfit:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-paper text-ink antialiased">
        <header class="mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
            <p class="font-serif text-2xl text-pine">Opportunity Hunter</p>
            <div class="flex gap-3 text-sm">
                @auth
                    <a class="rounded-md bg-pine px-3 py-2 text-white" href="{{ route('dashboard') }}">Dashboard</a>
                @else
                    <a class="px-3 py-2 text-bark" href="{{ route('login') }}">Log in</a>
                    <a class="rounded-md bg-clay px-3 py-2 text-white" href="{{ route('register') }}">Create account</a>
                @endauth
            </div>
        </header>
        <main class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:py-16">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-moss">For independent developers</p>
                <h1 class="mt-3 font-serif text-4xl leading-tight text-pine sm:text-5xl">Freelance work, matched to your own skills.</h1>
                <p class="mt-5 max-w-xl text-lg leading-relaxed text-bark">Opportunity Hunter collects public projects, lines them up with the profile you configure, and keeps proposal drafts in one place. It never applies, emails a client, or pretends to be you.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a class="rounded-md bg-clay px-4 py-2 text-sm font-medium text-white" href="{{ route('register') }}">Start with an account</a>
                    <a class="rounded-md border border-line bg-card px-4 py-2 text-sm font-medium" href="{{ route('login') }}">I already have one</a>
                </div>
            </div>
            <ol class="space-y-3">
                @foreach ([
                    'Discover' => 'Sources are collected on a schedule you control.',
                    'Normalize' => 'Listings land in one shape, no matter where they came from.',
                    'Match' => 'Your skills, keywords, budget, and workplace preferences do the filtering.',
                    'Review' => 'You write the proposal and update the status after you apply.',
                ] as $step => $copy)
                    <li class="rounded-xl border border-line bg-card px-5 py-4">
                        <p class="font-serif text-xl text-pine">{{ $step }}</p>
                        <p class="mt-1 text-sm text-bark">{{ $copy }}</p>
                    </li>
                @endforeach
            </ol>
        </main>
    </body>
</html>
