@props(['car', 'rank' => null, 'grid' => false])
@php($wrapper = $grid ? 'w-full' : ($rank ? 'w-[78vw] sm:w-[360px]' : 'w-[74vw] sm:w-[300px] xl:w-[320px]'))
<div {{ $attributes->merge(['class' => "group/tile relative shrink-0 snap-start {$wrapper}"]) }}>
    <a href="{{ route('cars.show', $car) }}" class="flex items-end outline-none" aria-label="{{ $car->name }}, RM {{ number_format($car->price_per_day, 0) }} per day">
        @if ($rank)
            <span class="rank-numeral -mr-5 shrink-0 sm:-mr-7" aria-hidden="true">{{ $rank }}</span>
        @endif

        <div class="relative aspect-[16/10] w-full overflow-hidden rounded-md bg-ink-700 shadow-tile ring-1 ring-white/[0.06] transition duration-300 ease-out will-change-transform group-hover/tile:z-10 group-hover/tile:scale-[1.07] group-hover/tile:ring-white/25 group-focus-visible/tile:ring-2 group-focus-visible/tile:ring-white">
            <x-car-photo :car="$car" class="transition duration-700 group-hover/tile:scale-105" />

            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/35 to-transparent opacity-90"></div>

            <div class="absolute top-2.5 left-2.5 flex flex-wrap gap-1.5">
                @if ($rank && $rank <= 3)
                    <span class="rounded-sm bg-brand px-1.5 py-0.5 text-[10px] leading-none font-black tracking-tight text-white">TOP<br>10</span>
                @endif
                @if ($car->is_featured)
                    <span class="badge badge-brand">Featured</span>
                @endif
                @if ($car->status === 'maintenance')
                    <span class="badge badge-warning">In service</span>
                @endif
            </div>

            <span class="absolute top-2.5 right-2.5 inline-flex items-center gap-1 rounded-full bg-black/60 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-white/80 opacity-0 backdrop-blur transition group-hover/tile:opacity-100">
                    <x-icon name="cube" class="size-3" /> 3D
                </span>

            <div class="absolute inset-x-0 bottom-0 p-3.5">
                <h3 class="display truncate text-[1.65rem] text-white">{{ $car->name }}</h3>
                <div class="mt-0.5 flex items-center gap-2 text-xs text-white/70">
                    <span class="font-semibold text-match">RM {{ number_format($car->price_per_day, 0) }}<span class="font-normal text-white/60">/day</span></span>
                    <span class="text-white/30">•</span>
                    <span>{{ $car->type }}</span>
                    <span class="text-white/30">•</span>
                    <span class="capitalize">{{ strtolower($car->transmission) }}</span>
                </div>

                <div class="grid grid-rows-[0fr] opacity-0 transition-all duration-300 group-hover/tile:grid-rows-[1fr] group-hover/tile:opacity-100 group-focus-visible/tile:grid-rows-[1fr] group-focus-visible/tile:opacity-100">
                    <div class="overflow-hidden">
                        <div class="mt-3 flex items-center gap-2">
                            <span class="grid size-8 place-items-center rounded-full bg-white text-black"><x-icon name="play" solid class="size-4" /></span>
                            <span class="grid size-8 place-items-center rounded-full ring-1 ring-white/50"><x-icon name="cube" class="size-4" /></span>
                            <span class="ml-auto inline-flex items-center gap-1 text-[11px] text-white/70"><x-icon name="map-pin" class="size-3.5" />{{ $car->branch?->name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </a>
</div>
