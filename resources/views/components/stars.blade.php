@props(['rating' => 0, 'size' => 'size-4'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-0.5']) }} role="img" aria-label="{{ number_format((float) $rating, 1) }} out of 5 stars">
    @for ($i = 1; $i <= 5; $i++)
        <x-icon name="star" solid class="{{ $size }} {{ $rating >= $i - 0.25 ? 'text-amber-400' : ($rating >= $i - 0.75 ? 'text-amber-400/50' : 'text-white/15') }}" />
    @endfor
</span>
