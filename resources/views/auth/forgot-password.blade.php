@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Reset your password</h1>
    <p class="mt-2 text-sm text-bark">We will email a reset link if an account exists for that address.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf
        <x-field label="Email" name="email" type="email" value="{{ old('email') }}" required />
        <x-button>Send reset link</x-button>
    </form>
@endsection
