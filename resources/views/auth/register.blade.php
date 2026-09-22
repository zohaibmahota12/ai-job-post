@extends('layouts.guest')

@section('title', 'Create account')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Create your account</h1>
    <p class="mt-2 text-sm text-bark">Your skills and matches stay private to this account.</p>
    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4">
        @csrf
        <x-field label="Name" name="name" value="{{ old('name') }}" required />
        <x-field label="Email" name="email" type="email" value="{{ old('email') }}" required />
        <x-field label="Password" name="password" type="password" required />
        <x-field label="Confirm password" name="password_confirmation" type="password" required />
        <x-button>Create account</x-button>
    </form>
    <p class="mt-4 text-sm text-bark">Already registered? <a class="underline" href="{{ route('login') }}">Log in</a></p>
@endsection
