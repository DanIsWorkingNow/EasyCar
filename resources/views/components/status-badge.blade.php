@props(['booking'])
@php($meta = $booking->statusMeta())
<x-badge :tone="$meta['tone']" {{ $attributes }}>{{ $meta['label'] }}</x-badge>
