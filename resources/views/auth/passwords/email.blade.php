@extends('layouts.auth')

@section('title', 'Reset password')

@section('content')
    <h1 class="text-3xl font-bold">Forgot your password?</h1>
    <p class="mt-2 text-sm text-mute">Enter your email and we'll send you a link to choose a new one.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-4">
        @csrf
        <x-field name="email" type="email" label="Email" required autofocus autocomplete="email" class="[&_.input]:h-12" />
        <button class="btn btn-primary btn-lg w-full">Email reset link</button>
    </form>
    <p class="mt-8 text-mute"><a href="{{ route('login') }}" class="inline-flex items-center gap-1.5 hover:text-white"><x-icon name="arrow-left" class="size-4" /> Back to sign in</a></p>
@endsection
