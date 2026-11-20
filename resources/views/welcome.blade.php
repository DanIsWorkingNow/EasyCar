@extends('layouts.app')

@section('title', 'Car rental, reimagined')
@section('nav_overlay', 'true')
@if ($featured?->photo_url) @section('og_image', $featured->photo_url) @endif

@section('content')
    {{-- ── Billboard ─────────────────────────────────────────────── --}}
    @if ($featured)
        <section class="relative isolate overflow-hidden" aria-label="Featured car">
            <div class="absolute inset-0 -z-10">
                <x-car-photo :car="$featured" eager class="object-cover object-[70%_center] sm:object-right" />
                <div class="absolute inset-0 bg-gradient-to-r from-ink via-ink/75 to-transparent sm:via-ink/55"></div>
                <div class="absolute inset-x-0 bottom-0 h-56 bg-gradient-to-t from-ink via-ink/70 to-transparent"></div>
                <div class="absolute inset-x-0 top-0 h-40 bg-gradient-to-b from-black/60 to-transparent"></div>
            </div>

            <div class="flex min-h-[680px] flex-col justify-end px-4 pt-28 pb-44 sm:min-h-[90vh] sm:px-8 sm:pb-56 lg:px-14">
                <div class="max-w-2xl animate-fade-up">
                    <p class="flex items-center gap-2 text-xs font-bold tracking-[0.3em] text-white/80 uppercase">
                        <span class="grid size-6 place-items-center rounded-sm bg-brand font-display text-base tracking-normal text-white">E</span>
                        Featured this week
                    </p>
                    <h1 class="display mt-4 text-[clamp(3.75rem,11vw,9.5rem)] text-white drop-shadow-[0_4px_24px_rgba(0,0,0,0.6)]"><span class="block">{{ $featured->brand }}</span><span class="block whitespace-nowrap">{{ $featured->model }}</span></h1>

                    <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm font-medium text-white/80 sm:text-base">
                        <span class="font-bold text-match">{{ $featured->status === 'available' ? 'Available now' : 'Popular pick' }}</span>
                        @if ($featured->year)<span>{{ $featured->year }}</span>@endif
                        <span class="rounded border border-white/40 px-1.5 text-xs leading-5">{{ $featured->type }}</span>
                        <span>{{ $featured->seats }} seats</span>
                        <span class="capitalize">{{ strtolower($featured->transmission) }}</span>
                        <span class="rounded border border-white/40 px-1.5 text-xs leading-5">3D</span>
                    </div>

                    <p class="mt-5 max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">
                        {{ $featured->description ?: "Spin it around in our 3D showroom, check live availability and lock it in for your next trip from {$featured->branch?->name}." }}
                    </p>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('bookings.create', ['car' => $featured->id]) }}" class="btn btn-light btn-lg">
                            <x-icon name="play" solid class="size-6" /> Book now
                        </a>
                        <a href="{{ route('cars.show', $featured) }}" class="btn btn-ghost btn-lg">
                            <x-icon name="cube" class="size-6" /> Explore in 3D
                        </a>
                        <span class="ml-1 text-sm text-white/70">from <span class="font-display text-2xl tracking-wide text-white">RM {{ number_format($featured->price_per_day, 0) }}</span>/day</span>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- ── Rows ──────────────────────────────────────────────────── --}}
    <div class="relative z-10 {{ $featured ? '-mt-28 sm:-mt-36' : 'pt-28' }} space-y-10 sm:space-y-12">
        @forelse ($rows as $row)
            <x-car-row :title="$row['title']" :cars="$row['cars']" :ranked="$row['ranked'] ?? false" :href="$row['href'] ?? route('cars.index')" />
        @empty
            <div class="container-ec"><x-empty-state title="The showroom is being prepared" description="New cars are on their way. Check back soon." /></div>
        @endforelse
    </div>

    {{-- ── How it works ──────────────────────────────────────────── --}}
    <section class="mt-24 border-y-[6px] border-ink-900">
        <div class="container-ec grid gap-12 py-20 lg:grid-cols-3 lg:gap-8">
            @foreach ([
                ['cube', 'Explore in 3D', 'Rotate, zoom and repaint every car in our interactive showroom before you commit.'],
                ['calendar', 'Book in a minute', 'Live availability across all branches. Pick your dates, pick up to two cars, done.'],
                ['shield', 'Pay securely', 'Card, FPX online banking or GrabPay through encrypted checkout. Automatic refunds if plans change.'],
            ] as [$icon, $title, $copy])
                <div class="flex gap-5">
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-brand/25 to-brand/5 ring-1 ring-brand/30">
                        <x-icon :name="$icon" class="size-7 text-brand" />
                    </span>
                    <div>
                        <h2 class="display text-3xl">{{ $title }}</h2>
                        <p class="mt-2 leading-relaxed text-mute">{{ $copy }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── Numbers + branches ────────────────────────────────────── --}}
    <section class="container-ec mt-20 grid gap-10 lg:grid-cols-[1fr_1.4fr] lg:items-center">
        <div>
            <p class="eyebrow">Across the Klang Valley</p>
            <h2 class="display mt-3 text-5xl sm:text-6xl">Pick up where<br>you are.</h2>
            <dl class="mt-8 grid grid-cols-3 gap-4">
                <div><dt class="text-xs text-subtle uppercase">Cars</dt><dd class="stat-value mt-1">{{ $stats['cars'] }}</dd></div>
                <div><dt class="text-xs text-subtle uppercase">Branches</dt><dd class="stat-value mt-1">{{ $stats['branches'] }}</dd></div>
                <div><dt class="text-xs text-subtle uppercase">Trips</dt><dd class="stat-value mt-1">{{ number_format($stats['trips']) }}+</dd></div>
            </dl>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ($branches as $branch)
                <a href="{{ route('cars.index', ['branch_id' => $branch->id]) }}" class="group card card-pad flex flex-col justify-between transition hover:-translate-y-1 hover:border-white/20">
                    <x-icon name="map-pin" class="size-6 text-brand" />
                    <div class="mt-8">
                        <h3 class="display text-3xl">{{ $branch->name }}</h3>
                        <p class="mt-1 text-sm text-subtle">{{ $branch->cars_count }} cars{{ $branch->city ? ' · '.$branch->city : '' }}</p>
                        <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-white/80 group-hover:text-white">Browse <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" /></span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ── FAQ ───────────────────────────────────────────────────── --}}
    <section class="mx-auto mt-24 max-w-4xl px-4" x-data="{ open: null }">
        <h2 class="display text-center text-5xl sm:text-6xl">Frequently asked questions</h2>
        <div class="mt-10 space-y-2">
            @foreach ([
                ['How far in advance do I need to book?', 'Bookings start at least 2 days from today. That gives our branch team time to prepare, inspect and clean your car.'],
                ['How do I pay?', 'Pay online right after booking with Visa, Mastercard, FPX online banking or GrabPay. Your car is held for '.config('payments.hold_minutes').' minutes while you complete payment.'],
                ['What if my booking is rejected or I cancel?', 'Cancel any time before pickup from My bookings. If you have already paid — or if we can\'t confirm your booking — you are refunded automatically to your original payment method.'],
                ['Can I book more than one car?', 'Yes — up to two cars for overlapping dates, perfect for family trips or events.'],
                ['What do I need at pickup?', 'A valid driving licence, your IC or passport, and your booking reference (it\'s in your confirmation email and on the booking page).'],
            ] as $i => [$q, $a])
                <div class="bg-ink-700">
                    <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="flex w-full items-center justify-between gap-6 px-6 py-5 text-left text-lg font-medium hover:bg-ink-600 sm:text-xl" :aria-expanded="open === {{ $i }}">
                        {{ $q }}
                        <x-icon name="plus" class="size-7 shrink-0 transition duration-300" ::class="open === {{ $i }} && 'rotate-45'" />
                    </button>
                    <div x-show="open === {{ $i }}" x-collapse x-cloak class="border-t border-ink px-6 py-5 text-base leading-relaxed text-white/80 sm:text-lg">{{ $a }}</div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ── CTA ───────────────────────────────────────────────────── --}}
    @guest
        <section class="mx-auto mt-16 max-w-3xl px-4 text-center">
            <p class="text-lg text-white/85">Ready to drive? Enter your email to create your account.</p>
            <form action="{{ route('register') }}" method="GET" class="mt-4 flex flex-col gap-2 sm:flex-row">
                <label class="sr-only" for="cta-email">Email address</label>
                <input id="cta-email" type="email" name="email" required placeholder="Email address" class="input h-14 flex-1 bg-black/60 text-base">
                <button class="btn btn-primary btn-lg h-14 text-xl">Get started <x-icon name="chevron-right" class="size-6" /></button>
            </form>
        </section>
    @endguest
@endsection
