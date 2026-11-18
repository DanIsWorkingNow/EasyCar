@props(['title', 'cars', 'ranked' => false, 'href' => null])
<section x-data="carousel" class="group/row relative" aria-label="{{ $title }}">
    <div class="flex items-baseline gap-3 px-4 sm:px-8 lg:px-14">
        <h2 class="text-lg font-semibold tracking-tight text-white/95 sm:text-[1.35rem]">{{ $title }}</h2>
        @if ($href)
            <a href="{{ $href }}" class="inline-flex items-center gap-0.5 text-xs font-semibold text-sky-300/90 opacity-0 transition group-hover/row:opacity-100 focus:opacity-100">Explore all <x-icon name="chevron-right" class="size-3.5" /></a>
        @endif
    </div>

    <div class="relative mt-1">
        <button type="button" x-show="canPrev" x-cloak @click="page(-1)" aria-label="Scroll {{ $title }} left"
                class="absolute inset-y-0 left-0 z-20 hidden w-12 place-items-center bg-gradient-to-r from-ink to-transparent text-white opacity-0 transition group-hover/row:opacity-100 sm:grid lg:w-14">
            <x-icon name="chevron-left" class="size-9 transition hover:scale-125" />
        </button>

        <div x-ref="track" class="row-scroller scroll-px-4 px-4 sm:scroll-px-8 sm:px-8 lg:scroll-px-14 lg:px-14">
            @foreach ($cars as $car)
                <x-car-tile :car="$car" :rank="$ranked ? $loop->iteration : null" />
            @endforeach
        </div>

        <button type="button" x-show="canNext" @click="page(1)" aria-label="Scroll {{ $title }} right"
                class="absolute inset-y-0 right-0 z-20 hidden w-12 place-items-center bg-gradient-to-l from-ink to-transparent text-white opacity-0 transition group-hover/row:opacity-100 sm:grid lg:w-14">
            <x-icon name="chevron-right" class="size-9 transition hover:scale-125" />
        </button>
    </div>
</section>
