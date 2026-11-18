@extends('layouts.auth')

@section('title', 'Choose a new password')

@section('content')
    <h1 class="text-3xl font-bold">Choose a new password</h1>
    <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-field name="email" type="email" label="Email" :value="$email ?? ''" required autocomplete="email" class="[&_.input]:h-12" />
        <x-field name="password" type="password" label="New password" required autofocus autocomplete="new-password" class="[&_.input]:h-12" />
        <x-field name="password_confirmation" type="password" label="Confirm password" required autocomplete="new-password" class="[&_.input]:h-12" />
        <button class="btn btn-primary btn-lg w-full">Reset password</button>
    </form>
@endsection
