@props(['car', 'eager' => false])
@if ($car->photo_url)
    <img src="{{ $car->photo_url }}" alt="{{ $car->name }}" @unless($eager) loading="lazy" @endunless decoding="async" {{ $attributes->merge(['class' => 'size-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'grid size-full place-items-center bg-gradient-to-br from-ink-600 via-ink-700 to-ink-900']) }}>
        <div class="text-center">
            <x-icon name="car" class="mx-auto size-10 text-white/25" />
            <span class="display mt-1 block text-2xl text-white/30">{{ $car->brand }}</span>
        </div>
    </div>
@endif
