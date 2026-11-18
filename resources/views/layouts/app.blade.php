<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink">
<head>
    @include('partials.head')
</head>
<body class="min-h-dvh">
    <a href="#main" class="sr-only z-[200] rounded bg-white px-4 py-2 font-semibold text-black focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

    @include('partials.navbar', ['overlay' => trim($__env->yieldContent('nav_overlay')) === 'true'])

    <x-flash />

    <main id="main" class="@unless(trim($__env->yieldContent('nav_overlay')) === 'true') pt-20 sm:pt-24 @endunless">
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
    @stack('scripts')
</body>
</html>
