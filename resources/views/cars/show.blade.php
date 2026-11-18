@extends('layouts.app')

@section('title', $car->name)
@section('description', "Rent the {$car->name} from RM ".number_format($car->price_per_day, 0)." a day at EasyCar {$car->branch?->name}. Explore it in 3D and book online.")
@if ($car->photo_url) @section('og_image', $car->photo_url) @endif

@section('content')
    <div class="relative">
        {{-- Ambient backdrop from the car photo --}}
        @if ($car->photo_url)
            <div class="pointer-events-none absolute inset-x-0 -top-24 -z-10 h-[680px] overflow-hidden opacity-30 blur-3xl" aria-hidden="true">
                <img src="{{ $car->photo_url }}" alt="" class="size-full scale-110 object-cover">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-ink/60 to-ink"></div>
            </div>
        @endif

        <div class="container-ec">
            <nav class="mb-6 flex items-center gap-2 text-sm text-subtle" aria-label="Breadcrumb">
                <a href="{{ route('cars.index') }}" class="hover:text-white">Cars</a>
                <x-icon name="chevron-right" class="size-3.5" />
                <a href="{{ route('cars.index', ['type' => $car->type]) }}" class="hover:text-white">{{ \App\Models\Car::pluralType($car->type) }}</a>
                <x-icon name="chevron-right" class="size-3.5" />
                <span class="text-white/80" aria-current="page">{{ $car->name }}</span>
            </nav>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1.65fr)_minmax(340px,1fr)] lg:gap-10">
                {{-- 3D showroom --}}
                <div class="min-w-0">
                    <div class="overflow-hidden rounded-2xl bg-[radial-gradient(ellipse_at_50%_35%,#1f1f1f_0%,#0b0b0b_70%)] ring-1 ring-white/[0.07]">
                        <x-car-viewer :car="$car" />
                    </div>
                    <p class="mt-3 flex items-center gap-2 text-xs text-subtle">
                        <x-icon name="info" class="size-4" />
                        @if ($car->model_3d)
                            Detailed 3D model of the {{ $car->name }}.
                        @else
                            Stylised 3D representation of {{ in_array($car->type, ['SUV', 'MPV']) ? 'an '.$car->type : 'a '.strtolower($car->type) }} — colours and proportions are indicative. See the photo below for the actual car.
                        @endif
                    </p>
                </div>

                {{-- Booking panel --}}
                <aside class="lg:sticky lg:top-28 lg:self-start">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge tone="neutral">{{ $car->type }}</x-badge>
                        @if ($car->is_featured)<x-badge tone="brand">Featured</x-badge>@endif
                        @if ($car->status === 'maintenance')
                            <x-badge tone="warning" icon="warning">In service</x-badge>
                        @else
                            <x-badge tone="success" icon="check">Ready to book</x-badge>
                        @endif
                    </div>
                    <h1 class="display mt-4 text-6xl sm:text-7xl">{{ $car->name }}</h1>
                    <p class="mt-1 text-sm text-mute">{{ $car->year ? $car->year.' · ' : '' }}<span class="font-mono">{{ $car->plate_number }}</span></p>

                    <div class="mt-6 flex items-end gap-2">
                        <span class="display text-5xl text-white">RM {{ number_format($car->price_per_day, 0) }}</span>
                        <span class="pb-1.5 text-sm text-subtle">per day · taxes included</span>
                    </div>

                    <dl class="mt-6 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-line ring-1 ring-line">
                        @foreach ([
                            ['users', 'Seats', $car->seats],
                            ['gear', 'Gearbox', ucfirst(strtolower($car->transmission))],
                            ['bolt', 'Fuel', $car->fuel_type],
                            ['map-pin', 'Pick-up', $car->branch?->name],
                        ] as [$icon, $label, $value])
                            <div class="flex items-center gap-3 bg-ink-800 px-4 py-3.5">
                                <x-icon :name="$icon" class="size-5 text-subtle" />
                                <div>
                                    <dt class="text-[11px] tracking-wider text-subtle uppercase">{{ $label }}</dt>
                                    <dd class="text-sm font-semibold">{{ $value }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>

                    <div class="mt-6 flex flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
                        @if ($car->status !== 'maintenance')
                            <a href="{{ route('bookings.create', ['car' => $car->id]) }}" class="btn btn-primary btn-lg flex-1"><x-icon name="play" solid class="size-5" /> Book this car</a>
                        @endif
                        <a href="{{ route('cars.index', ['type' => $car->type]) }}" class="btn btn-outline btn-lg flex-1">Compare {{ \App\Models\Car::pluralType($car->type) }}</a>
                    </div>

                    <ul class="mt-6 space-y-2.5 text-sm text-mute">
                        <li class="flex items-center gap-2.5"><x-icon name="shield" class="size-4 text-match" /> Free cancellation before pick-up — automatic refund</li>
                        <li class="flex items-center gap-2.5"><x-icon name="card" class="size-4 text-match" /> Pay by card, FPX or GrabPay</li>
                        <li class="flex items-center gap-2.5"><x-icon name="clock" class="size-4 text-match" /> Book at least 2 days ahead</li>
                    </ul>
                </aside>
            </div>

            {{-- Availability + details --}}
            <div class="mt-14 grid gap-8 lg:grid-cols-[minmax(0,1.65fr)_minmax(340px,1fr)] lg:gap-10">
                <section class="card card-pad">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-lg font-semibold">Availability — next 6 weeks</h2>
                        <div class="flex items-center gap-4 text-xs text-subtle">
                            <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-emerald-500/70"></span> Free</span>
                            <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm bg-white/10"></span> Booked</span>
                            <span class="flex items-center gap-1.5"><span class="size-3 rounded-sm ring-1 ring-white/20"></span> Too soon</span>
                        </div>
                    </div>
                    <div class="mt-5 grid grid-cols-7 gap-1.5 text-center text-[11px]">
                        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dow)
                            <div class="pb-1 font-semibold text-subtle uppercase">{{ $dow }}</div>
                        @endforeach
                        @for ($i = 0; $i < 42; $i++)
                            @php
                                $day = now()->startOfWeek()->addDays($i);
                                $iso = $day->toDateString();
                                $booked = $bookedDays->contains($iso);
                                $tooSoon = $day->lt(now()->startOfDay()->addDays(2));
                            @endphp
                            <div class="flex aspect-square flex-col items-center justify-center rounded-md text-xs
                                {{ $tooSoon ? 'text-white/25 ring-1 ring-white/[0.06]' : ($booked ? 'bg-white/[0.06] text-white/35 line-through' : 'bg-emerald-500/15 font-semibold text-emerald-200 ring-1 ring-emerald-500/25') }}"
                                 title="{{ $day->format('D, d M') }} — {{ $tooSoon ? 'too soon to book' : ($booked ? 'booked' : 'available') }}">
                                {{ $day->day }}
                                @if ($day->day === 1 || $i === 0)<span class="text-[9px] font-normal tracking-wide uppercase opacity-70">{{ $day->format('M') }}</span>@endif
                            </div>
                        @endfor
                    </div>
                </section>

                <section class="card overflow-hidden">
                    <div class="aspect-[16/10] bg-ink-700"><x-car-photo :car="$car" /></div>
                    <div class="card-pad">
                        <h2 class="text-lg font-semibold">About this car</h2>
                        <p class="mt-2 text-sm leading-relaxed text-mute">
                            {{ $car->description ?: "The {$car->name} is a comfortable ".strtolower($car->type)." with a ".strtolower($car->transmission)." gearbox and seating for {$car->seats}. Collected from and returned to our {$car->branch?->name} branch, inspected and cleaned before every rental." }}
                        </p>
                        @if ($car->branch?->address)
                            <p class="mt-4 flex items-start gap-2 text-sm text-mute"><x-icon name="map-pin" class="mt-0.5 size-4 text-brand" /> {{ $car->branch->address }}</p>
                        @endif
                    </div>
                </section>
            </div>
        </div>

        @if ($similar->isNotEmpty())
            <div class="mt-16">
                <x-car-row title="More like this" :cars="$similar" />
            </div>
        @endif
    </div>
@endsection
