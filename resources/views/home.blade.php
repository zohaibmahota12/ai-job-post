<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Opportunity Hunter</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|syne:600,700,800" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-pine text-white antialiased">
        <div class="relative min-h-screen overflow-hidden">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,_rgba(13,148,136,0.35),_transparent_42%),radial-gradient(circle_at_80%_0%,_rgba(255,255,255,0.08),_transparent_35%),linear-gradient(180deg,_transparent_40%,_rgba(0,0,0,0.35)_100%)]"></div>

            <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-4 py-6 sm:px-6">
                <p class="font-serif text-2xl font-bold tracking-tight text-white sm:text-3xl">Opportunity Hunter</p>
                <div class="flex items-center gap-2 text-sm sm:gap-3">
                    @auth
                        <a class="rounded-lg bg-moss px-3.5 py-2 font-medium text-white transition hover:bg-moss/90" href="{{ route('dashboard') }}">Dashboard</a>
                    @else
                        <a class="px-3 py-2 font-medium text-white/75 transition hover:text-white" href="{{ route('login') }}">Log in</a>
                        <a class="rounded-lg bg-moss px-3.5 py-2 font-medium text-white transition hover:bg-moss/90" href="{{ route('register') }}">Create account</a>
                    @endauth
                </div>
            </header>

            <main class="relative z-10 mx-auto grid w-full max-w-6xl gap-12 px-4 pb-16 pt-6 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:pb-24 lg:pt-10">
                <div class="animate-rise">
                    <h1 class="max-w-xl font-serif text-4xl font-bold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-[3.25rem]">
                        Freelance work, matched to your own skills.
                    </h1>
                    <p class="mt-5 max-w-lg text-base leading-relaxed text-white/70 sm:text-lg">
                        Collect public projects, score them against your profile, and keep proposal drafts in one place — without ever auto-applying for you.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a class="rounded-lg bg-moss px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-moss/90" href="{{ route('register') }}">Start with an account</a>
                        <a class="rounded-lg border border-white/20 bg-white/5 px-5 py-2.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/10" href="{{ route('login') }}">I already have one</a>
                    </div>
                </div>

                <div class="animate-rise-delay relative" aria-hidden="true">
                    <div class="absolute -inset-4 rounded-[2rem] bg-moss/20 blur-2xl"></div>
                    <div class="relative overflow-hidden rounded-2xl border border-white/10 bg-[#102a36] shadow-2xl shadow-black/40">
                        <div class="flex items-center gap-2 border-b border-white/10 px-4 py-3">
                            <span class="size-2.5 rounded-full bg-white/20"></span>
                            <span class="size-2.5 rounded-full bg-white/20"></span>
                            <span class="size-2.5 rounded-full bg-white/20"></span>
                            <span class="ml-3 text-xs font-medium text-white/45">High-match opportunities</span>
                        </div>
                        <div class="divide-y divide-white/8 p-2">
                            @foreach ([
                                ['Laravel API rebuild', 'Remote · $4–6k', 92],
                                ['Vue dashboard polish', 'Contract · EU', 84],
                                ['PHP integration sprint', 'Part-time · US', 78],
                            ] as [$title, $meta, $score])
                                <div class="flex items-start justify-between gap-4 rounded-xl px-3 py-3.5 transition hover:bg-white/5">
                                    <div>
                                        <p class="text-sm font-semibold text-white">{{ $title }}</p>
                                        <p class="mt-1 text-xs text-white/45">{{ $meta }}</p>
                                    </div>
                                    <p class="font-serif text-2xl font-bold text-moss">{{ $score }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t border-white/10 bg-black/20 px-4 py-3 text-xs text-white/40">
                            You review. You apply. Nothing sends itself.
                        </div>
                    </div>
                </div>
            </main>

            <section class="relative z-10 border-t border-white/10 bg-paper text-ink">
                <div class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-14 sm:px-6 sm:py-16 lg:grid-cols-4">
                    @foreach ([
                        'Discover' => 'Sources collect on a schedule you control.',
                        'Normalize' => 'Every listing lands in one consistent shape.',
                        'Match' => 'Skills, budget, and workplace preferences filter the noise.',
                        'Review' => 'You write the proposal and track status after you apply.',
                    ] as $step => $copy)
                        <div>
                            <p class="font-serif text-xl font-bold text-pine">{{ $step }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-bark">{{ $copy }}</p>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </body>
</html>
