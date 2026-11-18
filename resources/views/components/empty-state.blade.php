@props(['icon' => 'car', 'title', 'description' => null])
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-xl border border-dashed border-white/10 px-6 py-16 text-center']) }}>
    <div class="mb-4 grid size-14 place-items-center rounded-full bg-white/5 ring-1 ring-white/10">
        <x-icon :name="$icon" class="size-7 text-mute" />
    </div>
    <h3 class="text-lg font-semibold">{{ $title }}</h3>
    @if ($description)<p class="mt-1.5 max-w-sm text-sm text-mute">{{ $description }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-6 flex flex-wrap justify-center gap-3">{{ $slot }}</div>@endif
</div>
