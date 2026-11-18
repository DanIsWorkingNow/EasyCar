@extends('layouts.auth')

@section('title', 'Create account')

@section('content')
    <h1 class="text-3xl font-bold">Create your account</h1>
    <p class="mt-2 text-sm text-mute">Book in a minute, pay securely, cancel free before pick-up.</p>
    <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-4">
        @csrf
        <x-field name="name" label="Full name" required autofocus autocomplete="name" class="[&_.input]:h-12 [&_.input]:bg-ink-600/70" />
        <x-field name="email" type="email" label="Email" :value="request('email')" required autocomplete="email" class="[&_.input]:h-12 [&_.input]:bg-ink-600/70" />
        <x-field name="password" type="password" label="Password" required autocomplete="new-password" help="At least 8 characters." class="[&_.input]:h-12 [&_.input]:bg-ink-600/70" />
        <x-field name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" class="[&_.input]:h-12 [&_.input]:bg-ink-600/70" />
        <button class="btn btn-primary btn-lg mt-2 w-full">Create account</button>
    </form>
    <p class="mt-8 text-mute">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-white hover:underline">Sign in.</a></p>
@endsection
