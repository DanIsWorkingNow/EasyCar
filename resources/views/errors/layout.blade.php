<!doctype html>
<html lang="en" class="bg-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code') · {{ config('app.name', 'EasyCar') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @php($hasVite = file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @if ($hasVite)
        @vite(['resources/css/app.css'])
    @endif
    <style>
        body { margin: 0; background: #0b0b0b; color: #fff; font-family: Inter, system-ui, sans-serif; }
    </style>
</head>
<body class="relative grid min-h-dvh place-items-center overflow-hidden px-6">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_center,rgba(229,9,20,0.18)_0%,transparent_60%)]" aria-hidden="true"></div>
    <div class="relative max-w-xl text-center">
        <a href="{{ url('/') }}" class="font-display text-4xl tracking-wide text-[#e50914]" style="font-family: 'Bebas Neue', Impact, sans-serif;">EASY<span style="color:#fff">CAR</span></a>
        <p class="mt-10 font-display text-[9rem] leading-none tracking-wide text-white/10" style="font-family: 'Bebas Neue', Impact, sans-serif;">@yield('code')</p>
        <h1 class="-mt-6 text-3xl font-semibold sm:text-4xl">@yield('title')</h1>
        <p class="mx-auto mt-4 max-w-md text-[#b3b3b3]">@yield('message')</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="inline-flex items-center rounded-md bg-white px-6 py-3 font-semibold text-black hover:bg-white/80" style="text-decoration:none">EasyCar home</a>
            @yield('actions')
        </div>
    </div>
</body>
</html>
