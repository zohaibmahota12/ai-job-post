@extends('layouts.guest')

@section('title', 'Choose a new password')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Choose a new password</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field label="Email" name="email" type="email" value="{{ old('email', $email) }}" required />
        <x-field label="New password" name="password" type="password" required />
        <x-field label="Confirm password" name="password_confirmation" type="password" required />
        <x-button>Update password</x-button>
    </form>
@endsection
