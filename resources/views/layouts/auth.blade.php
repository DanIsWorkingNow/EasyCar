@php($posters = collect(glob(public_path('images/cars/*.jpeg')))->map(fn ($p) => asset('images/cars/'.basename($p)))->shuffle()->take(9))
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink">
<head>
    @include('partials.head')
</head>
<body class="relative min-h-dvh overflow-x-hidden">
    {{-- Poster wall backdrop --}}
    <div class="pointer-events-none fixed inset-0 -z-10" aria-hidden="true">
        <div class="absolute inset-0 grid -rotate-6 scale-125 grid-cols-3 gap-3 opacity-40">
            @foreach ($posters as $poster)
                <div class="aspect-[16/10] overflow-hidden rounded-md bg-ink-700"><img src="{{ $poster }}" alt="" class="size-full object-cover"></div>
            @endforeach
        </div>
        <div class="absolute inset-0 bg-gradient-to-b from-black/85 via-black/70 to-black"></div>
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,transparent_0%,rgba(0,0,0,0.75)_70%)]"></div>
    </div>

    <header class="flex h-20 items-center px-4 sm:px-10">
        <a href="{{ route('landing') }}" aria-label="{{ config('app.name') }} home"><x-logo class="text-[2.6rem]" /></a>
    </header>

    <x-flash />

    <main id="main" class="flex min-h-[calc(100dvh-5rem)] items-start justify-center px-4 pt-4 pb-16 sm:items-center sm:pt-0">
        <div class="w-full max-w-[440px] rounded-xl bg-black/75 px-6 py-10 shadow-2xl ring-1 ring-white/10 backdrop-blur-md sm:px-12 sm:py-12">
            @yield('content')
        </div>
    </main>

    @livewireScripts
</body>
</html>
