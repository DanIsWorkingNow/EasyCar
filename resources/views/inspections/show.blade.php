@extends(auth()->user()->consoleRoute() ? 'layouts.console' : 'layouts.app')

@section('title', ($inspection->isPickup() ? 'Pick-up' : 'Return').' report · '.$inspection->booking->reference)
@section('breadcrumb', 'Operations / Handover report')

@section('content')
    @php
        $booking = $inspection->booking;
        $car = $inspection->car;
        $console = (bool) auth()->user()->consoleRoute();
        $newDamage = $inspection->isPickup() ? [] : $inspection->newDamagesComparedTo($pickup);
        $back = $console
            ? route(auth()->user()->isAdmin() ? 'admin.bookings.show' : 'staff.bookings.show', $booking)
            : route('bookings.show', $booking);
    @endphp

    <div class="{{ $console ? '' : 'container-ec' }} max-w-5xl print:max-w-none print:text-black">
        <x-page-header :eyebrow="($inspection->isPickup() ? 'Pick-up' : 'Return').' report · '.$booking->reference" :title="$car->name" :subtitle="$inspection->performed_at->format('l, d M Y · g:i A').' · '.$car->branch?->name" :back="$back" class="print:mb-4">
            <x-slot:actions>
                <button type="button" onclick="window.print()" class="btn btn-outline print:hidden"><x-icon name="download" class="size-4" /> Print / save PDF</button>
            </x-slot:actions>
        </x-page-header>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,1fr)] print:grid-cols-2">
            <div class="space-y-6">
                <section class="card card-pad print:border-black/20 print:bg-white">
                    <h2 class="font-semibold">Readings</h2>
                    <dl class="mt-4 grid grid-cols-2 gap-5 sm:grid-cols-4">
                        <div><dt class="label print:text-black/60">Odometer</dt><dd class="text-lg font-semibold">{{ number_format($inspection->odometer_km) }} km</dd></div>
                        <div><dt class="label print:text-black/60">Fuel</dt><dd class="text-lg font-semibold">{{ \App\Models\VehicleInspection::fuelLabel($inspection->fuel_level) }}</dd></div>
                        @if ($pickup)
                            <div><dt class="label print:text-black/60">Driven</dt><dd class="text-lg font-semibold">{{ number_format(max(0, $inspection->odometer_km - $pickup->odometer_km)) }} km</dd></div>
                            <div><dt class="label print:text-black/60">Fuel vs pick-up</dt><dd class="text-lg font-semibold">{{ $inspection->fuel_level - $pickup->fuel_level >= 0 ? 'Same or more' : ($pickup->fuel_level - $inspection->fuel_level).'/8 short' }}</dd></div>
                        @else
                            <div><dt class="label print:text-black/60">Plate</dt><dd class="font-mono text-lg">{{ $car->plate_number }}</dd></div>
                            <div><dt class="label print:text-black/60">Due back</dt><dd class="text-sm font-semibold">{{ $booking->returnDueAt()->format('d M, g:i A') }}</dd></div>
                        @endif
                    </dl>
                </section>

                <section class="card card-pad print:border-black/20 print:bg-white">
                    <h2 class="font-semibold">Condition</h2>
                    <div class="mt-4 grid gap-6 sm:grid-cols-[220px_1fr]">
                        <x-damage-diagram :damages="$inspection->damages ?? []" :previous="$pickup?->damages ?? []" />
                        <div class="space-y-4">
                            <ul class="space-y-1.5 text-sm">
                                @foreach (config('rentals.checklist') as $key => $label)
                                    @php($ok = (bool) (($inspection->checklist ?? [])[$key] ?? false))
                                    <li class="flex items-center gap-2"><x-icon :name="$ok ? 'check-circle' : 'x-circle'" class="size-4 {{ $ok ? 'text-match' : 'text-red-400' }}" /> {{ $label }}</li>
                                @endforeach
                            </ul>
                            <div>
                                <p class="label print:text-black/60">Damage</p>
                                @forelse ($inspection->damages ?? [] as $zone => $kind)
                                    <p class="text-sm">{{ \App\Models\VehicleInspection::ZONES[$zone] ?? $zone }} — {{ \App\Models\VehicleInspection::DAMAGE_KINDS[$kind] ?? $kind }}
                                        @if (isset($newDamage[$zone]))<span class="badge badge-brand ml-1">New since pick-up</span>@endif
                                    </p>
                                @empty
                                    <p class="text-sm text-subtle">No damage recorded.</p>
                                @endforelse
                            </div>
                            @if ($inspection->notes)
                                <div><p class="label print:text-black/60">Notes</p><p class="text-sm whitespace-pre-line text-mute print:text-black">{{ $inspection->notes }}</p></div>
                            @endif
                            @if ($inspection->needs_maintenance)
                                <x-badge tone="warning" icon="warning">Sent to maintenance</x-badge>
                            @endif
                        </div>
                    </div>
                </section>

                @if ($inspection->photos->isNotEmpty())
                    <section class="card card-pad print:border-black/20 print:bg-white">
                        <h2 class="font-semibold">Photos ({{ $inspection->photos->count() }})</h2>
                        <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                            @foreach ($inspection->photos as $photo)
                                <a href="{{ route('inspections.photo', [$inspection, $photo]) }}" target="_blank" rel="noopener" class="block aspect-[4/3] overflow-hidden rounded-md bg-ink-700">
                                    <img src="{{ route('inspections.photo', [$inspection, $photo]) }}" alt="Handover photo {{ $loop->iteration }}" loading="lazy" class="size-full object-cover transition hover:scale-105">
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-6">
                @unless ($inspection->isPickup())
                    <section class="card card-pad print:border-black/20 print:bg-white">
                        <h2 class="font-semibold">Extra charges</h2>
                        <ul class="mt-3 space-y-2 text-sm">
                            @forelse ($charges as $charge)
                                <li class="flex items-start justify-between gap-3 {{ $charge->isVoided() ? 'text-subtle line-through' : '' }}">
                                    <span>{{ $charge->description }}</span>
                                    <span class="font-semibold whitespace-nowrap">RM {{ number_format($charge->amount, 2) }}</span>
                                </li>
                            @empty
                                <li class="text-subtle">None — returned on time, same fuel.</li>
                            @endforelse
                        </ul>
                    </section>
                @endunless

                <section class="card card-pad print:border-black/20 print:bg-white">
                    <h2 class="font-semibold">Sign-off</h2>
                    @if ($inspection->signature_path)
                        <img src="{{ route('inspections.signature', $inspection) }}" alt="Customer signature" class="mt-4 h-28 w-full rounded-md bg-white object-contain ring-1 ring-white/10">
                    @endif
                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-subtle">Customer</dt><dd class="text-right">{{ $inspection->signed_by_name ?? $booking->user->name }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-subtle">Staff</dt><dd class="text-right">{{ $inspection->performer?->name ?? '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-subtle">Booking</dt><dd class="font-mono">{{ $booking->reference }}</dd></div>
                    </dl>
                </section>

                @if ($pickup)
                    <a href="{{ route('inspections.show', $pickup) }}" class="card card-pad flex items-center justify-between text-sm hover:border-white/20 print:hidden">
                        <span>View pick-up report</span><x-icon name="chevron-right" class="size-4" />
                    </a>
                @endif
            </aside>
        </div>
    </div>
@endsection
