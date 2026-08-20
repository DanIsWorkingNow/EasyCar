<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap CSS is now imported inside resources/css/app.css, scoped to
         a "bootstrap" cascade layer so it can't silently override Tailwind
         utility classes (see the comment there for why). -->

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700,800" rel="stylesheet">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        /* Custom Navbar Styles */
        .modern-navbar {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
            backdrop-filter: blur(20px);
            border: none;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-btn {
            position: relative;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Add vertical bar separator between buttons except the last one */
        .modern-navbar .nav-btn:not(:last-child)::after {
            content: "|";
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: 700;
            opacity: 0.5;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .nav-btn.home-btn {
            background: linear-gradient(45deg, #10b981, #059669);
            border-color: transparent;
        }

        .nav-btn.home-btn:hover {
            background: linear-gradient(45deg, #059669, #047857);
            transform: translateY(-2px) scale(1.05);
        }

        .nav-btn.logout-btn {
            background: linear-gradient(45deg, #ef4444, #dc2626);
            border-color: transparent;
        }

        .nav-btn.logout-btn:hover {
            background: linear-gradient(45deg, #dc2626, #b91c1c);
            transform: translateY(-2px) scale(1.05);
        }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .nav-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 576px) {
            .nav-btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.85rem;
            }
        }

        /* Page Content Styling */
        .main-content {
            min-height: calc(100vh - 100px);
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        /* Shared compact dashboard components (stat cards + quick action buttons) */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
        }
        .stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 1.25rem;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-top: 4px solid var(--accent, #667eea);
    overflow: hidden;
}
        
        .stat-card .icon  { font-size: 1.75rem; }
        .stat-card .value { font-size: 1.75rem; font-weight: 800; color: #667eea; margin: .25rem 0; }
        .stat-card .label { font-size: .9rem; color: #6b7280; font-weight: 600; }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: .75rem;
        }
        .action-btn {
            display: block;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: transform .15s ease;
        }
        .action-btn:hover { transform: translateY(-2px); }
    </style>
</head>

<body style="margin: 0; font-family: 'Nunito', sans-serif;">
    <div id="app">
        <!-- Modern Navigation Bar -->
        <nav class="modern-navbar">
            <div class="container d-flex justify-content-center align-items-center flex-wrap gap-2">
                <a href="{{ url('/') }}" class="nav-btn home-btn">Home</a>

                @auth
                    @role('admin')
                        <a href="{{ route('admin.dashboard') }}" class="nav-btn">Admin</a>
                    @endrole

                    @role('staff')
                        <a href="{{ route('staff.dashboard') }}" class="nav-btn">Staff</a>
                    @endrole

                    <a href="{{ url('/bookings') }}" class="nav-btn">My Bookings</a>
                    <a href="{{ url('/cars') }}" class="nav-btn">Browse Cars</a>
                @endauth

                @guest
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" class="nav-btn">Login</a>
                    @endif

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="nav-btn">Register</a>
                    @endif
                @else
                    <a href="{{ route('logout') }}" class="nav-btn logout-btn"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                       Logout
                    </a>

                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                @endguest
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content py-4">
            @yield('content')
        </main>
    </div>

    <!-- Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>