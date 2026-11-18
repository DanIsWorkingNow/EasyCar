@props(['tone' => 'neutral', 'icon' => null])
<span {{ $attributes->merge(['class' => "badge badge-{$tone}"]) }}>
    @if ($icon)<x-icon :name="$icon" class="size-3.5" />@endif
    {{ $slot }}
</span>
