<div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
    <div class="min-w-0 space-y-6">
        {{-- Step 1: dates --}}
        <section class="card card-pad" aria-labelledby="step-dates">
            <div class="flex items-center gap-3">
                <span class="grid size-7 place-items-center rounded-full bg-brand text-sm font-bold">1</span>
                <h2 id="step-dates" class="text-lg font-semibold">When do you need it?</h2>
            </div>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="startDate" class="label">Pick-up date</label>
                    <input id="startDate" type="date" wire:model.live="startDate" min="{{ now()->addDays(2)->toDateString() }}" class="input @error('startDate') input-error @enderror">
                    @error('startDate')<p class="field-error">{{ $message }}</p>@else<p class="help">Earliest: {{ now()->addDays(2)->format('D, d M') }}</p>@enderror
                </div>
                <div>
                    <label for="endDate" class="label">Return date</label>
                    <input id="endDate" type="date" wire:model.live="endDate" min="{{ $startDate ? \Carbon\Carbon::parse($startDate)->addDay()->toDateString() : now()->addDays(3)->toDateString() }}" class="input @error('endDate') input-error @enderror">
                    @error('endDate')<p class="field-error">{{ $message }}</p>@else<p class="help">{{ $days ? $days.' '.Str::plural('day', $days).' rental' : 'Charged per calendar day, inclusive.' }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Step 2: cars --}}
        <section class="card card-pad" aria-labelledby="step-cars">
            <div class="flex flex-wrap items-center gap-3">
                <span class="grid size-7 place-items-center rounded-full bg-brand text-sm font-bold">2</span>
                <h2 id="step-cars" class="text-lg font-semibold">Choose up to two cars</h2>
                <span class="ml-auto text-sm text-mute">
                    @if ($hasDates)
                        <span class="font-semibold text-match">{{ $availableCount }}</span> of {{ $cars->count() }} available
                    @else
                        Pick dates to see live availability
                    @endif
                </span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-[1.4fr_1fr_1fr_1fr_auto]">
                <label class="relative block sm:col-span-2 xl:col-span-1">
                    <span class="sr-only">Search cars</span>
                    <x-icon name="search" class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-subtle" />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search brand or model" class="input pl-9">
                </label>
                <select wire:model.live="branchId" class="input" aria-label="Branch">
                    <option value="">All branches</option>
                    @foreach ($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach
                </select>
                <select wire:model.live="type" class="input" aria-label="Car type">
                    <option value="">All types</option>
                    @foreach (\App\Models\Car::TYPES as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                </select>
                <select wire:model.live="transmission" class="input" aria-label="Gearbox">
                    <option value="">Any gearbox</option>
                    @foreach (\App\Models\Car::TRANSMISSIONS as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
                </select>
                <button type="button" wire:click="clearFilters" class="btn btn-quiet">Reset</button>
            </div>

            @error('selectedCarIds')
                <div class="mt-4 flex items-center gap-2 rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-300 ring-1 ring-red-500/30" role="alert">
                    <x-icon name="warning" class="size-5" /> {{ $message }}
                </div>
            @enderror

            <div class="relative mt-5">
                <div wire:loading.delay class="absolute inset-0 z-10 grid place-items-start justify-center rounded-lg bg-ink-800/60 pt-10 backdrop-blur-[1px]">
                    <span class="size-8 animate-spin rounded-full border-2 border-white/20 border-t-brand"></span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                    @forelse ($cars as $car)
                        @php
                            $isUnavailable = $hasDates && in_array($car->id, $unavailableIds, true);
                            $isSelected = in_array($car->id, $selectedCarIds, true);
                        @endphp
                        <button type="button" wire:key="car-{{ $car->id }}" wire:click="toggleCar({{ $car->id }})" @disabled($isUnavailable) aria-pressed="{{ $isSelected ? 'true' : 'false' }}"
                                class="group relative overflow-hidden rounded-lg bg-ink-700 text-left ring-1 transition
                                       {{ $isSelected ? 'ring-2 ring-brand shadow-glow' : 'ring-white/[0.07] hover:ring-white/25' }}
                                       {{ $isUnavailable ? 'cursor-not-allowed opacity-40 grayscale' : '' }}">
                            <div class="relative aspect-[16/9] overflow-hidden">
                                <x-car-photo :car="$car" class="transition duration-500 group-hover:scale-105" />
                                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-transparent to-transparent"></div>
                                <span class="absolute top-2 right-2 grid size-7 place-items-center rounded-full transition {{ $isSelected ? 'bg-brand text-white' : 'bg-black/60 text-transparent ring-1 ring-white/40' }}">
                                    <x-icon name="check" class="size-4" />
                                </span>
                                @if ($isUnavailable)
                                    <span class="absolute top-2 left-2 badge badge-neutral bg-black/70">Booked for these dates</span>
                                @endif
                                <div class="absolute inset-x-0 bottom-0 p-3">
                                    <p class="display text-2xl leading-none">{{ $car->name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 px-3 py-2.5 text-xs text-mute">
                                <span class="flex items-center gap-2"><span>{{ $car->type }}</span><span class="text-white/20">•</span><span class="capitalize">{{ strtolower($car->transmission) }}</span><span class="text-white/20">•</span><span>{{ $car->branch?->name }}</span></span>
                                <span class="font-semibold text-white">RM {{ number_format($car->price_per_day, 0) }}<span class="font-normal text-subtle">/d</span></span>
                            </div>
                        </button>
                    @empty
                        <div class="sm:col-span-2 2xl:col-span-3">
                            <x-empty-state icon="search" title="No cars match these filters">
                                <button type="button" wire:click="clearFilters" class="btn btn-outline">Reset filters</button>
                            </x-empty-state>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    {{-- Step 3: summary --}}
    <aside class="lg:sticky lg:top-28 lg:self-start" aria-labelledby="step-review">
        <div class="card overflow-hidden">
            <div class="flex items-center gap-3 border-b border-line px-6 py-4">
                <span class="grid size-7 place-items-center rounded-full bg-brand text-sm font-bold">3</span>
                <h2 id="step-review" class="text-lg font-semibold">Your trip</h2>
            </div>

            <div class="space-y-5 p-6">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-white/[0.03] p-3 ring-1 ring-line">
                        <p class="text-[11px] tracking-wider text-subtle uppercase">Pick-up</p>
                        <p class="mt-1 font-semibold">{{ $startDate ? \Carbon\Carbon::parse($startDate)->format('D, d M') : '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-white/[0.03] p-3 ring-1 ring-line">
                        <p class="text-[11px] tracking-wider text-subtle uppercase">Return</p>
                        <p class="mt-1 font-semibold">{{ $endDate ? \Carbon\Carbon::parse($endDate)->format('D, d M') : '—' }}</p>
                    </div>
                </div>

                <ul class="space-y-3">
                    @forelse ($selectedCars as $car)
                        <li class="flex items-center gap-3" wire:key="sel-{{ $car->id }}">
                            <div class="h-12 w-20 shrink-0 overflow-hidden rounded bg-ink-700"><x-car-photo :car="$car" /></div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $car->name }}</p>
                                <p class="text-xs text-subtle">RM {{ number_format($car->price_per_day, 0) }} × {{ $days ?? '—' }} {{ Str::plural('day', $days ?? 2) }}</p>
                            </div>
                            <button type="button" wire:click="toggleCar({{ $car->id }})" class="rounded p-1 text-subtle hover:text-white" aria-label="Remove {{ $car->name }}"><x-icon name="x" class="size-4" /></button>
                        </li>
                    @empty
                        <li class="rounded-lg border border-dashed border-white/10 px-4 py-6 text-center text-sm text-subtle">No car selected yet</li>
                    @endforelse
                </ul>

                <div class="divider pt-4">
                    @if ($quote)
                        @foreach ($quote['lines'] as $line)
                            <div class="flex justify-between text-sm text-mute"><span>{{ $line['name'] }}</span><span>RM {{ number_format($line['price'], 2) }}</span></div>
                        @endforeach
                    @endif
                    <div class="mt-3 flex items-end justify-between">
                        <span class="text-sm text-mute">Total</span>
                        <span class="display text-4xl">RM {{ number_format($quote['total'] ?? 0, 2) }}</span>
                    </div>
                </div>

                <label class="flex items-start gap-3 text-sm text-mute">
                    <input type="checkbox" wire:model="acceptTerms" class="checkbox mt-0.5">
                    <span>I accept the <a href="{{ route('legal.terms') }}" target="_blank" class="text-white underline">Terms of Rental</a> and have read the <a href="{{ route('legal.privacy') }}" target="_blank" class="text-white underline">Privacy Notice</a>.</span>
                </label>

                @if ($errors->hasAny(['selectedCarIds', 'startDate', 'endDate', 'acceptTerms']))
                    <p class="flex items-start gap-2 rounded-lg bg-red-500/10 px-3 py-2.5 text-sm text-red-300 ring-1 ring-red-500/30" role="alert">
                        <x-icon name="warning" class="mt-0.5 size-4 shrink-0" /> {{ $errors->first('selectedCarIds') ?: ($errors->first('startDate') ?: ($errors->first('endDate') ?: $errors->first('acceptTerms'))) }}
                    </p>
                @endif

                @if (config('compliance.require_verified_driver') && ! auth()->user()->isVerifiedDriver())
                    <p class="flex items-start gap-2 text-xs leading-relaxed text-sky-200/90">
                        <x-icon name="shield" class="size-4 shrink-0" />
                        <span>You can reserve now. We'll confirm the booking once your <a href="{{ route('verification.show') }}" class="underline" target="_blank">driving licence is verified</a>.</span>
                    </p>
                @endif

                <button type="button" wire:click="createBooking" wire:loading.attr="disabled" wire:target="createBooking" class="btn btn-primary btn-lg w-full" @disabled(! $quote)>
                    <span wire:loading.remove wire:target="createBooking">{{ config('payments.enabled') ? 'Reserve & continue to payment' : 'Request booking' }}</span>
                    <span wire:loading wire:target="createBooking" class="inline-flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> Reserving…</span>
                </button>
                <p class="flex items-start gap-2 text-xs leading-relaxed text-subtle">
                    <x-icon name="shield" class="size-4 shrink-0 text-match" />
                    @if (config('payments.enabled'))
                        We hold your car for {{ config('payments.hold_minutes') }} minutes while you pay. Free cancellation before pick-up.
                    @else
                        No payment needed now. Free cancellation before pick-up.
                    @endif
                </p>
            </div>
        </div>
    </aside>
</div>
