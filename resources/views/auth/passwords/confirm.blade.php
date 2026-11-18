@extends('layouts.auth')

@section('title', 'Confirm password')

@section('content')
    <x-icon name="lock" class="size-10 text-brand" />
    <h1 class="mt-4 text-3xl font-bold">Confirm it's you</h1>
    <p class="mt-2 text-sm text-mute">Please confirm your password before continuing.</p>
    <form method="POST" action="{{ route('password.confirm') }}" class="mt-7 space-y-4">
        @csrf
        <x-field name="password" type="password" label="Password" required autofocus autocomplete="current-password" class="[&_.input]:h-12" />
        <button class="btn btn-primary btn-lg w-full">Confirm</button>
        @if (Route::has('password.request'))
            <a class="block text-center text-sm text-mute hover:text-white" href="{{ route('password.request') }}">Forgot your password?</a>
        @endif
    </form>
@endsection
