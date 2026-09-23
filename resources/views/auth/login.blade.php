@extends('layouts.guest')

@section('title', 'Log in')

@section('content')
    <h1 class="font-serif text-3xl font-bold tracking-tight text-pine">Log in</h1>
    <p class="mt-2 text-sm text-bark">Welcome back — review matches, then apply yourself.</p>
    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf
        <x-field label="Email" name="email" type="email" value="{{ old('email') }}" required />
        <x-field label="Password" name="password" type="password" required />
        <label class="flex items-center gap-2 text-sm text-bark">
            <input type="checkbox" name="remember" value="1">
            Remember this browser
        </label>
        <x-button>Log in</x-button>
    </form>
    <p class="mt-4 text-sm text-bark">
        <a class="underline" href="{{ route('password.request') }}">Forgot password</a>
        <span class="px-2">·</span>
        <a class="underline" href="{{ route('register') }}">Create account</a>
    </p>
@endsection
