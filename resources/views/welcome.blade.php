<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>EasyCar - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Tailwind CSS (via Vite or CDN fallback) -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
</head>
<body class="min-h-screen bg-gradient-to-r from-red-500 via-pink-500 to-red-600 text-blue-900 flex flex-col">

    <header class="flex justify-end p-6 space-x-6">
        @if (Route::has('login'))
            @auth
                <a href="{{ url('/home') }}" class="hover:underline">Dashboard</a>
                <a href="{{ url('/logout') }}" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="hover:underline">Logout</a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
            @else
                <a href="{{ route('login') }}" class="hover:underline">Login</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="hover:underline">Register</a>
                @endif
            @endauth
        @endif
    </header>

    <main class="flex-grow flex flex-col justify-center items-center px-6">
        <h1 class="text-5xl font-extrabold mb-12 drop-shadow-lg text-center text-blue-900">
            Welcome to EasyCar
        </h1>
        <p class="max-w-xl text-center text-lg mb-16 drop-shadow-md text-blue-900">
            Your trusted Malaysian car rental platform. Book your ride or manage your bookings with ease.
        </p>

        @auth
            <div class="flex flex-col sm:flex-row gap-8 w-full max-w-md">
                <a href="{{ url('/bookings') }}" 
                   class="flex-grow bg-white text-red-600 font-bold rounded-lg py-6 text-center text-2xl shadow-lg hover:scale-105 hover:shadow-xl transition transform">
                    Bookings
                </a>
                <a href="{{ url('/cars') }}" 
                   class="flex-grow bg-white text-red-600 font-bold rounded-lg py-6 text-center text-2xl shadow-lg hover:scale-105 hover:shadow-xl transition transform">
                    Cars
                </a>
            </div>
        @else
            <a href="{{ route('register') }}" 
               class="inline-block bg-white text-red-600 font-bold rounded-lg py-4 px-10 text-2xl shadow-lg hover:scale-105 hover:shadow-xl transition transform">
                Get Started - Register Now
            </a>
        @endauth
    </main>

    <footer class="text-center p-4 text-blue-900/70">
        &copy; {{ date('Y') }} EasyCar. All rights reserved.
    </footer>

</body>
</html>
