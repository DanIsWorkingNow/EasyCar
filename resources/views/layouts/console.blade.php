@php
    $user = auth()->user();
    $isAdmin = $user->isAdmin();
    $nav = $isAdmin
        ? [
            ['Overview', 'admin.dashboard', 'grid', 'admin.dashboard'],
            ['Operations', 'ops.board', 'car', 'ops.*'],
            ['Bookings', 'admin.bookings.index', 'calendar', 'admin.bookings.*'],
            ['Payments', 'admin.payments.index', 'card', 'admin.payments.*'],
            ['Fleet', 'admin.cars.index', 'cube', 'admin.cars.*'],
            ['Branches', 'admin.branches.index', 'building', 'admin.branches.*'],
            ['Users', 'admin.users.index', 'users', 'admin.users.*'],
        ]
        : [
            ['Overview', 'staff.dashboard', 'grid', 'staff.dashboard'],
            ['Operations', 'ops.board', 'car', 'ops.*'],
            ['Bookings', 'staff.bookings.index', 'calendar', 'staff.bookings.*'],
            ['Branch fleet', 'staff.cars.index', 'cube', 'staff.cars.*'],
        ];
    $pending = \App\Models\Booking::pending()
        ->when(! $isAdmin, fn ($q) => $q->whereHas('cars', fn ($c) => $c->where('branch_id', $user->branch_id)))
        ->count();
    $opsSummary = ($isAdmin || $user->branch_id) ? app(\App\Services\Rentals\RentalOperationsService::class)->summary($isAdmin ? null : $user->branch_id) : [];
    $opsDue = ($opsSummary['overdue'] ?? 0) + ($opsSummary['pickups_today'] ?? 0) + ($opsSummary['returns_today'] ?? 0);
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink">
<head>
    @include('partials.head')
</head>
<body class="min-h-dvh bg-ink-900" x-data="{ sidebar: false }">
    <a href="#main" class="sr-only z-[200] rounded bg-white px-4 py-2 font-semibold text-black focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    {{-- Sidebar --}}
    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-black/70 lg:hidden" @click="sidebar = false"></div>
    <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-line bg-ink transition-transform duration-300">
        <div class="flex h-16 items-center justify-between px-5">
            <a href="{{ route($user->consoleRoute()) }}"><x-logo class="text-[2rem]" /></a>
            <span class="badge {{ $isAdmin ? 'badge-brand' : 'badge-info' }}">{{ $isAdmin ? 'Admin' : 'Staff' }}</span>
        </div>

        @unless ($isAdmin)
            <div class="mx-4 mb-2 flex items-center gap-2 rounded-lg bg-white/[0.03] px-3 py-2 text-xs text-mute ring-1 ring-line">
                <x-icon name="map-pin" class="size-4 text-brand" /> {{ $user->branch?->name ?? 'No branch assigned' }}
            </div>
        @endunless

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-3" aria-label="Console">
            @foreach ($nav as [$label, $route, $icon, $pattern])
                <a href="{{ route($route) }}" class="console-link {{ request()->routeIs($pattern) ? 'console-link-active' : '' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>
                    <x-icon :name="$icon" class="size-[18px]" />
                    <span class="flex-1">{{ $label }}</span>
                    @if ($label === 'Bookings' && $pending)
                        <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-bold text-amber-300">{{ $pending }}</span>
                    @elseif ($label === 'Operations' && $opsDue)
                        <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ ($opsSummary['overdue'] ?? 0) ? 'bg-red-500/20 text-red-300' : 'bg-emerald-500/15 text-emerald-300' }}">{{ $opsDue }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="space-y-1 border-t border-line p-3">
            <a href="{{ route('landing') }}" class="console-link"><x-icon name="external" class="size-[18px]" /> View storefront</a>
            <a href="{{ route('profile.edit') }}" class="console-link"><x-icon name="cog" class="size-[18px]" /> Settings</a>
            <div class="flex items-center gap-3 rounded-lg px-3 py-2.5">
                <x-avatar :user="$user" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                    <p class="truncate text-xs text-subtle">{{ $user->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-md p-1.5 text-subtle hover:bg-white/5 hover:text-white" title="Sign out" aria-label="Sign out"><x-icon name="logout" class="size-4" /></button></form>
            </div>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-ink-900/90 px-4 backdrop-blur sm:px-8">
            <button type="button" class="-ml-1 rounded-md p-2 text-mute hover:text-white lg:hidden" @click="sidebar = true" aria-label="Open navigation"><x-icon name="menu" /></button>
            <p class="text-sm text-subtle">@yield('breadcrumb', $isAdmin ? 'Admin console' : 'Staff console')</p>
            <div class="ml-auto flex items-center gap-2">
                @yield('header_actions')
            </div>
        </header>

        <x-flash />

        <main id="main" class="px-4 py-8 sm:px-8 lg:py-10">
            <div class="mx-auto max-w-[1400px]">
                @yield('content')
            </div>
        </main>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
