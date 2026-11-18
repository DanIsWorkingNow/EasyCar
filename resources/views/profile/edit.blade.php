@extends(auth()->user()->consoleRoute() ? 'layouts.console' : 'layouts.app')

@section('title', 'Account settings')

@section('content')
    <div class="{{ auth()->user()->consoleRoute() ? '' : 'container-ec' }} max-w-3xl">
        <x-page-header eyebrow="Account" title="Settings" />

        <div class="space-y-6">
            <form method="POST" action="{{ route('profile.update') }}" class="card">
                @csrf @method('PUT')
                <div class="card-pad space-y-5">
                    <div><h2 class="text-lg font-semibold">Profile</h2><p class="text-sm text-subtle">Your name and email appear on bookings and receipts.</p></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="name" label="Full name" :value="$user->name" required autocomplete="name" />
                        <x-field name="email" label="Email" type="email" :value="$user->email" required autocomplete="email" />
                    </div>
                </div>
                <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary">Save profile</button></div>
            </form>

            <form method="POST" action="{{ route('profile.password') }}" class="card">
                @csrf @method('PUT')
                <div class="card-pad space-y-5">
                    <div><h2 class="text-lg font-semibold">Password</h2><p class="text-sm text-subtle">Use at least 8 characters.</p></div>
                    <x-field name="current_password" label="Current password" type="password" bag="password" required autocomplete="current-password" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="password" label="New password" type="password" bag="password" required autocomplete="new-password" />
                        <x-field name="password_confirmation" label="Confirm new password" type="password" bag="password" required autocomplete="new-password" />
                    </div>
                </div>
                <div class="flex justify-end border-t border-line px-6 py-4"><button class="btn btn-primary">Change password</button></div>
            </form>
        </div>
    </div>
@endsection
