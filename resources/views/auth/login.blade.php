@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
    <h1 class="text-3xl font-bold">Sign in</h1>
    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-4">
        @csrf
        <x-field name="email" type="email" label="Email" required autofocus autocomplete="email" class="[&_.input]:h-12 [&_.input]:bg-ink-600/70" />
        <x-field name="password" type="password" label="Password" required autocomplete="current-password" class="[&_.input]:h-12 [&_.input]:bg-ink-600/70" />
        <button class="btn btn-primary btn-lg mt-2 w-full">Sign in</button>
        <div class="flex items-center justify-between text-sm text-mute">
            <label class="flex items-center gap-2"><input type="checkbox" name="remember" class="checkbox" @checked(old('remember'))> Remember me</label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="hover:text-white hover:underline">Forgot password?</a>
            @endif
        </div>
    </form>
    <p class="mt-10 text-mute">New to EasyCar? <a href="{{ route('register') }}" class="font-semibold text-white hover:underline">Create an account.</a></p>
    @if (app()->isLocal())
        <div class="mt-6 rounded-lg bg-white/[0.04] p-3 text-xs text-subtle ring-1 ring-white/10">
            <p class="font-semibold text-mute">Demo accounts (local only) — password <span class="font-mono">password</span></p>
            <p class="mt-1 font-mono">admin@easycar.com · staff.bangi@easycar.com · customer1@easycar.com</p>
        </div>
    @endif
@endsection
