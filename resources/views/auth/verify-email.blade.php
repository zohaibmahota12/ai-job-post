@extends('layouts.app')

@section('title', 'Verify email')

@section('content')
    <h1 class="font-serif text-3xl text-pine">Confirm your email</h1>
    <p class="mt-3 max-w-xl text-bark">Open the verification link we sent to {{ auth()->user()->email }}. The dashboard stays locked until that link is used. Mail is written to the application log until you configure SMTP.</p>
    <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
        @csrf
        <x-button>Send the link again</x-button>
    </form>
@endsection
