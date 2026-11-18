@props(['label', 'value', 'icon' => null, 'tone' => 'text-white', 'hint' => null])
<div {{ $attributes->merge(['class' => 'card card-pad relative overflow-hidden']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-semibold tracking-wider text-subtle uppercase">{{ $label }}</p>
        @if ($icon)<x-icon :name="$icon" class="size-5 text-subtle" />@endif
    </div>
    <p class="stat-value mt-3 {{ $tone }}">{{ $value }}</p>
    @if ($hint)<p class="mt-1.5 text-xs text-subtle">{{ $hint }}</p>@endif
</div>
