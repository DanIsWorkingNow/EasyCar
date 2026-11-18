@php($footerBranches = \Illuminate\Support\Facades\Cache::remember('footer:branches', 600, fn () => \App\Models\Branch::orderBy('name')->get(['name', 'address', 'city', 'phone'])))
<footer class="mt-24 border-t border-line bg-ink">
    <div class="container-ec grid gap-10 py-14 md:grid-cols-[1.4fr_1fr_1fr_1.4fr]">
        <div>
            <x-logo class="text-4xl" />
            <p class="mt-3 max-w-xs text-sm leading-relaxed text-subtle">Car rental, reimagined. Explore every car in 3D, reserve in a minute and pay securely online.</p>
            <div class="mt-5 flex flex-wrap items-center gap-2 text-[11px] font-semibold text-mute">
                <span class="inline-flex items-center gap-1.5 rounded border border-line px-2 py-1"><x-icon name="lock" class="size-3.5" /> Secure checkout</span>
                <span class="rounded border border-line px-2 py-1">VISA</span>
                <span class="rounded border border-line px-2 py-1">Mastercard</span>
                <span class="rounded border border-line px-2 py-1">FPX</span>
                <span class="rounded border border-line px-2 py-1">GrabPay</span>
            </div>
        </div>
        <div>
            <h3 class="text-xs font-semibold tracking-wider text-white uppercase">Explore</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-subtle">
                <li><a href="{{ route('cars.index') }}" class="hover:text-white">All cars</a></li>
                <li><a href="{{ route('cars.index', ['type' => 'SUV']) }}" class="hover:text-white">SUVs</a></li>
                <li><a href="{{ route('cars.index', ['type' => 'Sedan']) }}" class="hover:text-white">Sedans</a></li>
                <li><a href="{{ route('cars.index', ['type' => 'MPV']) }}" class="hover:text-white">Family MPVs</a></li>
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-semibold tracking-wider text-white uppercase">Account</h3>
            <ul class="mt-4 space-y-2.5 text-sm text-subtle">
                @auth
                    <li><a href="{{ route('bookings.index') }}" class="hover:text-white">My bookings</a></li>
                    <li><a href="{{ route('profile.edit') }}" class="hover:text-white">Account settings</a></li>
                    <li><a href="{{ route('notifications.index') }}" class="hover:text-white">Notifications</a></li>
                @else
                    <li><a href="{{ route('login') }}" class="hover:text-white">Sign in</a></li>
                    <li><a href="{{ route('register') }}" class="hover:text-white">Create account</a></li>
                @endauth
            </ul>
        </div>
        <div>
            <h3 class="text-xs font-semibold tracking-wider text-white uppercase">Branches</h3>
            <ul class="mt-4 space-y-3 text-sm text-subtle">
                @foreach ($footerBranches as $branch)
                    <li class="flex gap-2">
                        <x-icon name="map-pin" class="mt-0.5 size-4 text-brand" />
                        <span><span class="font-medium text-white/85">{{ $branch->name }}</span>@if ($branch->address)<br><span class="text-xs">{{ $branch->address }}</span>@endif @if ($branch->phone)<br><span class="text-xs">{{ $branch->phone }}</span>@endif</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="border-t border-line">
        <div class="container-ec flex flex-col items-center justify-between gap-2 py-6 text-xs text-subtle sm:flex-row">
            <p>&copy; {{ now()->year }} {{ config('app.name', 'EasyCar') }}. All rights reserved.</p>
            <p>Prices in Malaysian Ringgit (MYR). Bookings require 2 days' notice.</p>
        </div>
    </div>
</footer>
