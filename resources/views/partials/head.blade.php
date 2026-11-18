<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0b0b0b">
<meta name="color-scheme" content="dark">

@php($pageTitle = trim($__env->yieldContent('title')))
<title>{{ $pageTitle ? $pageTitle.' · ' : '' }}{{ config('app.name', 'EasyCar') }}</title>
<meta name="description" content="@yield('description', 'Rent your next car in minutes. Explore every car in 3D, book instantly and pay securely with card or FPX across Bangi, Shah Alam and Gombak.')">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name', 'EasyCar') }}">
<meta property="og:title" content="{{ $pageTitle ?: config('app.name', 'EasyCar').' — Car rental, reimagined' }}">
<meta property="og:description" content="@yield('description', 'Explore every car in 3D, book instantly and pay securely.')">
@hasSection('og_image')<meta property="og:image" content="@yield('og_image')">@endif
<link rel="canonical" href="{{ url()->current() }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
@stack('head')
