@props(['car', 'variant' => 'icon'])
@php
    $on = auth()->check() && in_array($car->id, auth()->user()->favouriteCarIds(), true);
    $iconClasses = $variant === 'icon'
        ? 'grid size-9 place-items-center rounded-full bg-black/55 text-white ring-1 ring-white/30 backdrop-blur transition hover:scale-110 hover:ring-white'
        : 'btn btn-ghost btn-lg';
@endphp
@auth
    <form method="POST" action="{{ route('favourites.toggle', $car) }}" x-data="favourite(@js(route('favourites.toggle', $car)), @js($on))" @submit.prevent="toggle" {{ $attributes }}>
        @csrf
        <button type="submit" class="{{ $iconClasses }}" :aria-pressed="on.toString()" aria-pressed="{{ $on ? 'true' : 'false' }}"
                :aria-label="on ? @js('Remove '.$car->name.' from My List') : @js('Add '.$car->name.' to My List')" aria-label="{{ $on ? 'Remove '.$car->name.' from My List' : 'Add '.$car->name.' to My List' }}">
            <x-icon name="heart" class="size-[18px] transition {{ $on ? 'fill-brand text-brand' : '' }}" ::class="{ 'fill-brand text-brand': on }" />
            @if ($variant !== 'icon')
                <span x-text="on ? 'In My List' : 'My List'">{{ $on ? 'In My List' : 'My List' }}</span>
            @endif
        </button>
    </form>
@else
    <a href="{{ route('login') }}" class="{{ $iconClasses }}" aria-label="Sign in to add {{ $car->name }} to My List" {{ $attributes }}>
        <x-icon name="heart" class="size-[18px]" />
        @if ($variant !== 'icon')
            <span>My List</span>
        @endif
    </a>
@endauth
