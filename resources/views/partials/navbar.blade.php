@php
    $user = auth()->user();
    $unread = $user ? $user->unreadNotifications()->count() : 0;
    $links = [
        ['label' => 'Home', 'route' => $user ? 'home' : 'landing', 'active' => request()->routeIs('landing', 'home')],
        ['label' => 'Browse cars', 'route' => 'cars.index', 'active' => request()->routeIs('cars.*')],
    ];
    if ($user) {
        $links[] = ['label' => 'My bookings', 'route' => 'bookings.index', 'active' => request()->routeIs('bookings.*', 'payments.*')];
    }
@endphp
<header x-data="navbar" :class="scrolled || mobileOpen || {{ $overlay ? 'false' : 'true' }} ? 'bg-ink/95 shadow-[0_1px_0_rgba(255,255,255,0.06)] backdrop-blur-md' : 'bg-gradient-to-b from-black/80 to-transparent'"
        class="fixed inset-x-0 top-0 z-50 transition-colors duration-500">
    <nav class="flex h-16 items-center gap-6 px-4 sm:h-[72px] sm:px-8 lg:px-14" aria-label="Main">
        <a href="{{ route($user ? 'home' : 'landing') }}" class="shrink-0" aria-label="{{ config('app.name') }} home"><x-logo class="text-[2rem] sm:text-[2.35rem]" /></a>

        <div class="hidden items-center gap-1 md:flex">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" class="nav-link {{ $link['active'] ? 'nav-link-active font-semibold' : '' }}" @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            @if ($user?->consoleRoute())
                <a href="{{ route($user->consoleRoute()) }}" class="nav-link {{ request()->is('admin*', 'staff*') ? 'nav-link-active' : '' }}">{{ $user->isAdmin() ? 'Admin console' : 'Staff console' }}</a>
            @endif
        </div>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-3">
            <form action="{{ route('cars.index') }}" method="GET" role="search" x-data="{ open: {{ request()->filled('q') ? 'true' : 'false' }} }" class="relative hidden sm:block">
                <div :class="open ? 'w-64 border-white/70 bg-black/80' : 'w-10 border-transparent'" class="flex items-center overflow-hidden rounded-md border transition-all duration-300">
                    <button type="button" @click="open = !open; $nextTick(() => $refs.q.focus())" class="grid size-10 shrink-0 place-items-center text-white" aria-label="Search cars">
                        <x-icon name="search" />
                    </button>
                    <input x-ref="q" name="q" value="{{ request('q') }}" placeholder="Brand, model, type…" @keydown.escape="open = false" @blur="if (! $el.value) open = false"
                           class="w-full bg-transparent pr-3 text-sm text-white placeholder:text-subtle focus:outline-none" aria-label="Search cars">
                </div>
            </form>

            @auth
                <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" class="relative grid size-10 place-items-center rounded-md text-white hover:bg-white/5" aria-label="Notifications{{ $unread ? " ({$unread} unread)" : '' }}" :aria-expanded="open">
                        <x-icon name="bell" />
                        @if ($unread)
                            <span class="absolute top-1.5 right-1.5 grid min-w-4 place-items-center rounded-full bg-brand px-1 text-[10px] leading-4 font-bold">{{ min($unread, 9) }}{{ $unread > 9 ? '+' : '' }}</span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right @click.outside="open = false"
                         class="absolute right-0 mt-2 w-80 overflow-hidden rounded-lg border border-line bg-ink-900/98 shadow-2xl backdrop-blur">
                        <div class="flex items-center justify-between border-b border-line px-4 py-3">
                            <p class="text-sm font-semibold">Notifications</p>
                            @if ($unread)
                                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="text-xs text-mute hover:text-white">Mark all read</button></form>
                            @endif
                        </div>
                        <ul class="max-h-80 divide-y divide-line overflow-y-auto">
                            @forelse ($user->notifications()->latest()->limit(6)->get() as $note)
                                <li>
                                    <a href="{{ route('notifications.open', $note->id) }}" class="flex gap-3 px-4 py-3 text-sm hover:bg-white/5">
                                        <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $note->read_at ? 'bg-transparent' : 'bg-brand' }}"></span>
                                        <span class="min-w-0">
                                            <span class="block text-white/90">{{ $note->data['message'] ?? 'Update on your booking' }}</span>
                                            <span class="mt-0.5 block text-xs text-subtle">{{ $note->created_at->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                </li>
                            @empty
                                <li class="px-4 py-8 text-center text-sm text-subtle">You're all caught up.</li>
                            @endforelse
                        </ul>
                        <a href="{{ route('notifications.index') }}" class="block border-t border-line px-4 py-2.5 text-center text-xs font-semibold text-mute hover:text-white">View all</a>
                    </div>
                </div>

                <div x-data="{ open: false }" class="relative" @keydown.escape="open = false">
                    <button type="button" @click="open = !open" class="flex items-center gap-1.5 rounded-md p-1 hover:bg-white/5" :aria-expanded="open" aria-label="Account menu">
                        <x-avatar :user="$user" />
                        <x-icon name="chevron-down" class="hidden size-4 text-mute transition sm:block" ::class="open && 'rotate-180'" />
                    </button>
                    <div x-show="open" x-cloak x-transition.origin.top.right @click.outside="open = false"
                         class="absolute right-0 mt-2 w-64 overflow-hidden rounded-lg border border-line bg-ink-900/98 py-1 shadow-2xl backdrop-blur">
                        <div class="flex items-center gap-3 border-b border-line px-4 py-3">
                            <x-avatar :user="$user" size="size-10 text-sm" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                                <p class="truncate text-xs text-subtle">{{ $user->email }}</p>
                            </div>
                        </div>
                        @if ($user->consoleRoute())
                            <a href="{{ route($user->consoleRoute()) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-mute hover:bg-white/5 hover:text-white"><x-icon name="grid" class="size-4" /> {{ $user->isAdmin() ? 'Admin console' : 'Staff console' }}</a>
                        @endif
                        <a href="{{ route('bookings.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-mute hover:bg-white/5 hover:text-white"><x-icon name="calendar" class="size-4" /> My bookings</a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-mute hover:bg-white/5 hover:text-white"><x-icon name="user" class="size-4" /> Account settings</a>
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                            @csrf
                            <button class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-mute hover:bg-white/5 hover:text-white"><x-icon name="logout" class="size-4" /> Sign out of EasyCar</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-quiet hidden sm:inline-flex">Sign in</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm sm:px-4 sm:py-2 sm:text-sm">Get started</a>
            @endauth

            <button type="button" class="grid size-10 place-items-center rounded-md text-white hover:bg-white/5 md:hidden" @click="mobileOpen = !mobileOpen" :aria-expanded="mobileOpen" aria-label="Menu">
                <x-icon name="menu" x-show="!mobileOpen" />
                <x-icon name="x" x-show="mobileOpen" x-cloak />
            </button>
        </div>
    </nav>

    <div x-show="mobileOpen" x-cloak x-transition.opacity class="border-t border-line px-4 pb-6 md:hidden">
        <form action="{{ route('cars.index') }}" method="GET" class="mt-4">
            <label class="relative block">
                <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-subtle" />
                <input name="q" value="{{ request('q') }}" placeholder="Search cars" class="input pl-9" aria-label="Search cars">
            </label>
        </form>
        <div class="mt-3 grid gap-1">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" class="rounded-md px-3 py-3 text-base font-medium {{ $link['active'] ? 'bg-white/5 text-white' : 'text-mute' }}">{{ $link['label'] }}</a>
            @endforeach
            @if ($user?->consoleRoute())
                <a href="{{ route($user->consoleRoute()) }}" class="rounded-md px-3 py-3 text-base font-medium text-mute">{{ $user->isAdmin() ? 'Admin console' : 'Staff console' }}</a>
            @endif
            @guest
                <a href="{{ route('login') }}" class="rounded-md px-3 py-3 text-base font-medium text-mute">Sign in</a>
            @endguest
        </div>
    </div>
</header>
