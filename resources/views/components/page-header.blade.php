@props(['title', 'eyebrow' => null, 'subtitle' => null, 'back' => null])
<div {{ $attributes->merge(['class' => 'mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($back)
            <a href="{{ $back }}" class="mb-3 inline-flex items-center gap-1.5 text-sm text-mute hover:text-white"><x-icon name="arrow-left" class="size-4" /> Back</a>
        @endif
        @if ($eyebrow)<p class="eyebrow mb-2">{{ $eyebrow }}</p>@endif
        <h1 class="display text-4xl sm:text-5xl">{{ $title }}</h1>
        @if ($subtitle)<p class="mt-2 max-w-2xl text-sm text-mute sm:text-base">{{ $subtitle }}</p>@endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endif
</div>
