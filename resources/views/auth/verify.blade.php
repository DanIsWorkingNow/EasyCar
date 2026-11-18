@extends('layouts.auth')

@section('title', 'Verify your email')

@section('content')
    <x-icon name="mail" class="size-10 text-brand" />
    <h1 class="mt-4 text-3xl font-bold">Check your inbox</h1>
    <p class="mt-3 text-mute">We've sent a verification link to your email address. Click it to activate your account.</p>
    <form method="POST" action="{{ route('verification.resend') }}" class="mt-8">
        @csrf
        <button class="btn btn-outline w-full">Resend verification email</button>
    </form>
@endsection
