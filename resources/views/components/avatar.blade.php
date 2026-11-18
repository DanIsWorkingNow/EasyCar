@props(['user', 'size' => 'size-8 text-xs'])
<span {{ $attributes->merge(['class' => "inline-grid shrink-0 place-items-center rounded-md bg-gradient-to-br from-brand to-brand-deep font-bold text-white {$size}"]) }} aria-hidden="true">{{ $user->initials }}</span>
