<div>
    @php
        $booking = $this->booking;
        $car = $this->car;
        $pickup = $this->pickup;
        $isReturn = $this->isReturn();
        $quote = $this->quote;
        $extrasTotal = collect($extraCharges)->sum(fn ($c) => is_numeric($c['amount'] ?? null) ? (float) $c['amount'] : 0);
        $quoteTotal = collect($quote)->sum('amount') + $extrasTotal;
        $kmDriven = $isReturn && $pickup && is_numeric($odometer) ? max(0, (int) $odometer - $pickup->odometer_km) : null;
    @endphp

    {{-- Header --}}
    <div class="card mb-6 flex flex-col gap-5 p-5 sm:flex-row sm:items-center">
        <div class="aspect-[16/10] w-full shrink-0 overflow-hidden rounded-lg bg-ink-700 sm:w-48"><x-car-photo :car="$car" /></div>
        <div class="min-w-0 flex-1">
            <p class="eyebrow">{{ $isReturn ? 'Return check-in' : 'Pick-up handover' }} · {{ $booking->reference }}</p>
            <h1 class="display mt-1 text-4xl sm:text-5xl">{{ $car->name }}</h1>
            <p class="mt-1 text-sm text-mute"><span class="font-mono text-white">{{ $car->plate_number }}</span> · {{ $car->branch?->name }} · {{ $booking->user->name }} ({{ $booking->user->email }})</p>
        </div>
        <dl class="grid grid-cols-2 gap-4 text-sm sm:text-right">
            <div><dt class="label">Pick-up</dt><dd class="font-semibold">{{ $booking->pickupDueAt()->format('D d M, g:i A') }}</dd></div>
            <div><dt class="label">Due back</dt><dd class="font-semibold {{ $booking->isOverdue() ? 'text-red-400' : '' }}">{{ $booking->returnDueAt()->format('D d M, g:i A') }}</dd></div>
        </dl>
    </div>

    @if ($this->blocker)
        <div class="card card-pad flex items-start gap-3 border-amber-500/30 bg-amber-500/[0.06]" role="alert">
            <x-icon name="warning" class="mt-0.5 size-5 text-amber-300" />
            <div>
                <p class="font-semibold text-amber-200">{{ $this->blocker }}</p>
                <a href="{{ route(auth()->user()->isAdmin() ? 'admin.bookings.show' : 'staff.bookings.show', $booking) }}" class="mt-2 inline-flex items-center gap-1 text-sm text-mute hover:text-white">Open booking <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(340px,1fr)]">
            <div class="space-y-6">
                @error('handover')<p class="rounded-lg bg-red-500/10 px-4 py-3 text-sm text-red-300 ring-1 ring-red-500/30" role="alert">{{ $message }}</p>@enderror

                {{-- 1. Readings --}}
                <section class="card card-pad">
                    <h2 class="flex items-center gap-2 font-semibold"><span class="grid size-6 place-items-center rounded-full bg-brand text-xs font-bold">1</span> Readings</h2>
                    <div class="mt-5 grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="odometer" class="label">Odometer (km)</label>
                            <input id="odometer" type="number" inputmode="numeric" min="0" wire:model.live.debounce.400ms="odometer" class="input text-lg font-semibold @error('odometer') input-error @enderror">
                            @error('odometer')
                                <p class="field-error">{{ $message }}</p>
                            @else
                                <p class="help">
                                    @if ($isReturn && $pickup)
                                        Pick-up: {{ number_format($pickup->odometer_km) }} km @if ($kmDriven !== null) · <span class="text-white">{{ number_format($kmDriven) }} km driven</span> @endif
                                    @else
                                        Read it off the dashboard.
                                    @endif
                                </p>
                            @enderror
                        </div>
                        <div>
                            <span class="label">Fuel level — {{ \App\Models\VehicleInspection::fuelLabel($fuel) }}</span>
                            <div class="flex items-end gap-1" role="radiogroup" aria-label="Fuel level">
                                @for ($i = 0; $i <= 8; $i++)
                                    <button type="button" wire:click="setFuel({{ $i }})" role="radio" aria-checked="{{ $fuel === $i ? 'true' : 'false' }}" aria-label="{{ \App\Models\VehicleInspection::fuelLabel($i) }}"
                                            class="flex-1 rounded-sm transition {{ $i <= $fuel ? ($fuel <= 2 ? 'bg-red-500' : ($fuel <= 4 ? 'bg-amber-400' : 'bg-match')) : 'bg-white/10 hover:bg-white/20' }}"
                                            style="height: {{ 14 + $i * 3 }}px"></button>
                                @endfor
                            </div>
                            <div class="mt-1 flex justify-between text-[10px] font-semibold text-subtle"><span>E</span><span>½</span><span>F</span></div>
                            @if ($isReturn && $pickup)
                                <p class="help">Pick-up: {{ \App\Models\VehicleInspection::fuelLabel($pickup->fuel_level) }}@if ($fuel < $pickup->fuel_level) · <span class="text-amber-300">{{ $pickup->fuel_level - $fuel }}/8 short</span>@endif</p>
                            @endif
                        </div>
                    </div>
                </section>

                {{-- 2. Condition --}}
                <section class="card card-pad">
                    <h2 class="flex items-center gap-2 font-semibold"><span class="grid size-6 place-items-center rounded-full bg-brand text-xs font-bold">2</span> Condition</h2>
                    <div class="mt-5 grid gap-8 lg:grid-cols-[260px_1fr]">
                        <div>
                            <x-damage-diagram :damages="$damages" :previous="$isReturn ? ($pickup?->damages ?? []) : []" interactive />
                            <p class="help text-center">Tap a panel to cycle: scratch → dent → crack → clear.</p>
                        </div>
                        <div class="space-y-5">
                            <fieldset>
                                <legend class="label">Checklist</legend>
                                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 2xl:grid-cols-2">
                                    @foreach (config('rentals.checklist') as $key => $label)
                                        <label class="flex cursor-pointer items-center gap-3 rounded-lg bg-white/[0.03] px-3 py-2.5 text-sm ring-1 ring-line hover:ring-white/20">
                                            <input type="checkbox" wire:model="checklist.{{ $key }}" class="checkbox"> {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <div>
                                <p class="label">Damage recorded</p>
                                @if (empty($damages))
                                    <p class="text-sm text-subtle">No damage marked.</p>
                                @else
                                    <ul class="space-y-1.5 text-sm">
                                        @foreach ($damages as $zone => $kind)
                                            @php($isNew = $isReturn && (($pickup?->damages ?? [])[$zone] ?? null) !== $kind)
                                            <li class="flex items-center gap-2">
                                                <span class="badge {{ $kind === 'crack' ? 'badge-danger' : 'badge-warning' }}">{{ $kinds[$kind] }}</span>
                                                <span>{{ $zones[$zone] }}</span>
                                                @if ($isNew)<span class="badge badge-brand">New</span>@endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>

                            <x-field name="notes" label="Notes (optional)" type="textarea" rows="3" wire:model="notes" placeholder="e.g. Small stone chip on windscreen, passenger side." />
                        </div>
                    </div>
                </section>

                {{-- 3. Photos --}}
                <section class="card card-pad">
                    <h2 class="flex items-center gap-2 font-semibold"><span class="grid size-6 place-items-center rounded-full bg-brand text-xs font-bold">3</span> Photos <span class="text-xs font-normal text-subtle">— all four sides, dashboard, any damage</span></h2>
                    <label class="mt-4 flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border border-dashed border-white/15 px-6 py-8 text-center hover:border-white/35">
                        <x-icon name="camera" class="size-8 text-mute" />
                        <span class="text-sm font-semibold">Take or upload photos</span>
                        <span class="text-xs text-subtle">Up to {{ config('rentals.photos.max_count') }} · {{ round(config('rentals.photos.max_kb') / 1024) }} MB each · stored privately</span>
                        <input type="file" wire:model="photos" multiple accept="image/*" capture="environment" class="sr-only">
                    </label>
                    <div wire:loading wire:target="photos" class="mt-3 text-sm text-mute">Uploading…</div>
                    @error('photos')<p class="field-error">{{ $message }}</p>@enderror
                    @error('photos.*')<p class="field-error">{{ $message }}</p>@enderror
                    @if ($photos)
                        <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6">
                            @foreach ($photos as $i => $photo)
                                <div class="group relative aspect-square overflow-hidden rounded-md bg-ink-700" wire:key="photo-{{ $i }}">
                                    @if (method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                        <img src="{{ $photo->temporaryUrl() }}" alt="Handover photo {{ $i + 1 }}" class="size-full object-cover">
                                    @endif
                                    <button type="button" wire:click="removePhoto({{ $i }})" class="absolute top-1 right-1 grid size-6 place-items-center rounded-full bg-black/70 text-white opacity-0 transition group-hover:opacity-100 focus:opacity-100" aria-label="Remove photo {{ $i + 1 }}"><x-icon name="x" class="size-3.5" /></button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                {{-- Return: extras --}}
                @if ($isReturn)
                    <section class="card card-pad">
                        <h2 class="flex items-center gap-2 font-semibold"><span class="grid size-6 place-items-center rounded-full bg-brand text-xs font-bold">4</span> Extra charges</h2>
                        <p class="mt-1 text-sm text-subtle">Late return, fuel and mileage are calculated from the policy. Add damage or cleaning below.</p>

                        <ul class="mt-4 divide-y divide-line rounded-lg ring-1 ring-line">
                            @forelse ($quote as $line)
                                <li class="flex items-center justify-between gap-4 px-4 py-3 text-sm">
                                    <span><span class="badge badge-neutral mr-2">{{ $line['type']->label() }}</span>{{ $line['description'] }}</span>
                                    <span class="font-semibold whitespace-nowrap">RM {{ number_format($line['amount'], 2) }}</span>
                                </li>
                            @empty
                                <li class="px-4 py-3 text-sm text-subtle">No automatic charges — on time, same fuel{{ config('rentals.daily_km_allowance') ? ', within mileage' : '' }}.</li>
                            @endforelse
                        </ul>

                        <div class="mt-4 space-y-3">
                            @foreach ($extraCharges as $i => $charge)
                                <div class="grid gap-2 rounded-lg bg-white/[0.03] p-3 ring-1 ring-line sm:grid-cols-[140px_1fr_130px_auto]" wire:key="extra-{{ $i }}">
                                    <select wire:model="extraCharges.{{ $i }}.type" class="input" aria-label="Charge type">
                                        @foreach ($chargeTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                                    </select>
                                    <input wire:model="extraCharges.{{ $i }}.description" class="input" placeholder="What and where (e.g. dent, rear bumper)" aria-label="Charge description">
                                    <input wire:model.live.debounce.400ms="extraCharges.{{ $i }}.amount" type="number" step="0.01" min="0" class="input" placeholder="RM" aria-label="Charge amount">
                                    <button type="button" wire:click="removeExtraCharge({{ $i }})" class="btn btn-quiet btn-icon" aria-label="Remove charge"><x-icon name="trash" class="size-4" /></button>
                                    @error("extraCharges.$i.description")<p class="field-error sm:col-span-4">{{ $message }}</p>@enderror
                                    @error("extraCharges.$i.amount")<p class="field-error sm:col-span-4">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                            <button type="button" wire:click="addExtraCharge" class="btn btn-outline btn-sm"><x-icon name="plus" class="size-4" /> Add damage / cleaning charge</button>
                        </div>

                        <label class="mt-5 flex items-start gap-3 rounded-lg bg-amber-500/[0.06] px-4 py-3 ring-1 ring-amber-500/25">
                            <input type="checkbox" wire:model="needsMaintenance" class="checkbox mt-0.5">
                            <span><span class="block text-sm font-semibold">Send to maintenance</span><span class="text-xs text-subtle">Hides the car from booking until an admin sets it back to available.</span></span>
                        </label>
                    </section>
                @endif
            </div>

            {{-- Right column: payment + sign-off --}}
            <aside class="space-y-6 xl:sticky xl:top-24 xl:self-start">
                @if ($this->paymentRequired)
                    <section class="card card-pad border-amber-500/30">
                        <h2 class="flex items-center gap-2 font-semibold"><x-icon name="cash" class="text-amber-300" /> Collect payment</h2>
                        <p class="mt-1 text-sm text-mute">This booking hasn't been paid online. Take <span class="font-semibold text-white">RM {{ number_format($booking->total_price, 2) }}</span> before handing over the keys.</p>
                        @error('payment')<p class="field-error">{{ $message }}</p>@enderror
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="label" for="paymentMethod">Paid by</label>
                                <select id="paymentMethod" wire:model="paymentMethod" class="input @error('paymentMethod') input-error @enderror">
                                    <option value="">Choose…</option>
                                    @foreach ($methods as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                                </select>
                                @error('paymentMethod')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <x-field name="paymentReference" label="Receipt / approval code (optional)" wire:model="paymentReference" />
                        </div>
                    </section>
                @endif

                @if ($this->depositDue)
                    <section class="card card-pad border-sky-500/30">
                        <h2 class="flex items-center gap-2 font-semibold"><x-icon name="shield" class="text-sky-300" /> Security deposit</h2>
                        <p class="mt-1 text-sm text-mute">Take a refundable deposit of <span class="font-semibold text-white">RM {{ number_format($booking->deposit_amount, 2) }}</span>. It's released, or used for any extra charges, after the return.</p>
                        @error('deposit')<p class="field-error">{{ $message }}</p>@enderror
                        <div class="mt-4 space-y-3">
                            <div>
                                <label class="label" for="depositMethod">Taken by</label>
                                <select id="depositMethod" wire:model="depositMethod" class="input @error('depositMethod') input-error @enderror">
                                    <option value="">Choose…</option>
                                    @foreach (config('pricing.deposit.methods') as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                                </select>
                                @error('depositMethod')<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                            <x-field name="depositReference" label="Pre-auth / receipt code (optional)" wire:model="depositReference" />
                        </div>
                    </section>
                @endif

                <section class="card card-pad">
                    <h2 class="font-semibold">Customer sign-off</h2>

                    @unless ($isReturn)
                        <label class="mt-4 flex items-start gap-3 text-sm">
                            <input type="checkbox" wire:model="licenceChecked" class="checkbox mt-0.5">
                            <span>I've checked the customer's <span class="font-semibold">driving licence</span> and <span class="font-semibold">IC / passport</span>, and they match the booking.</span>
                        </label>
                        @error('licenceChecked')<p class="field-error">{{ $message }}</p>@enderror
                    @endunless

                    <div class="mt-4">
                        <x-field name="signedByName" label="Customer name" wire:model="signedByName" required />
                    </div>

                    <div class="mt-4" x-data="signaturePad('signature')" wire:ignore>
                        <div class="flex items-center justify-between">
                            <span class="label">Signature</span>
                            <button type="button" class="text-xs text-mute hover:text-white" @click="clear()">Clear</button>
                        </div>
                        <canvas x-ref="canvas" class="h-40 w-full touch-none rounded-lg bg-white ring-1 ring-white/20" aria-label="Signature pad"></canvas>
                        <p class="help" x-text="empty ? 'Ask the customer to sign above.' : 'Signed.'">Ask the customer to sign above.</p>
                    </div>
                    @error('signature')<p class="field-error">{{ $message }}</p>@enderror

                    <p class="mt-4 text-xs leading-relaxed text-subtle">
                        By signing, the customer confirms the readings, condition and photos above{{ $isReturn ? ' and the extra charges' : '' }}.
                    </p>
                </section>

                <section class="card card-pad">
                    @if ($isReturn)
                        <div class="flex items-end justify-between">
                            <span class="text-sm text-mute">Extra charges</span>
                            <span class="display text-4xl">RM {{ number_format($quoteTotal, 2) }}</span>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary btn-lg mt-4 w-full" wire:loading.attr="disabled" wire:target="submit,photos">
                        <span wire:loading.remove wire:target="submit">{{ $isReturn ? 'Complete check-in' : 'Hand over keys' }}</span>
                        <span wire:loading wire:target="submit" class="inline-flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span> Saving…</span>
                    </button>
                    @if ($errors->any())
                        <p class="mt-3 text-center text-xs text-red-300">Please fix the highlighted fields.</p>
                    @endif
                </section>
            </aside>
        </form>
    @endif
</div>
