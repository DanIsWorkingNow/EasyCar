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
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">

    <!-- Header Navigation -->
    <header style="display: flex; justify-content: flex-end; padding: 2rem; position: relative; z-index: 10;">
        @if (Route::has('login'))
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                @auth
                    <a href="{{ url('/home') }}" 
                       style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.3);"
                       onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                        🏠 Dashboard
                    </a>
                    <a href="{{ url('/logout') }}" 
                       onclick="event.preventDefault();document.getElementById('logout-form').submit();"
                       style="background: rgba(239,68,68,0.2); backdrop-filter: blur(10px); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; border: 1px solid rgba(239,68,68,0.3);"
                       onmouseover="this.style.background='rgba(239,68,68,0.3)'; this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.background='rgba(239,68,68,0.2)'; this.style.transform='translateY(0)'">
                        🚪 Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
                @else
                    <a href="{{ route('login') }}" 
                       style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.3);"
                       onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                        🔑 Login
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                           style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);"
                           onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(16, 185, 129, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(16, 185, 129, 0.3)'">
                        📝 Register
                        </a>
                    @endif
                @endauth
            </div>
        @endif
    </header>

    <!-- Main Content -->
    <main style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 2rem; margin-top: -4rem;">
        
        <!-- Hero Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 30px; padding: 4rem 3rem; margin-bottom: 3rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); text-align: center; max-width: 800px; width: 100%; position: relative; overflow: hidden;">
            
            <!-- Decorative Elements -->
            <div style="position: absolute; top: -50px; right: -50px; width: 100px; height: 100px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1; animation: float 6s ease-in-out infinite;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 60px; height: 60px; background: linear-gradient(45deg, #10b981, #059669); border-radius: 50%; opacity: 0.1; animation: float 4s ease-in-out infinite reverse;"></div>
            
            <!-- Main Heading -->
            <h1 style="font-size: 3.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0 0 1rem 0; line-height: 1.2;">
                🚗 Welcome to EasyCar
            </h1>
            
            <!-- Subtitle -->
            <p style="color: #6b7280; font-size: 1.3rem; margin: 0 0 2rem 0; line-height: 1.6; max-width: 600px; margin-left: auto; margin-right: auto;">
                Your trusted Malaysian car rental platform. Experience premium vehicles, seamless booking, and exceptional service for every journey.
            </p>

            <!-- Feature Highlights -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin: 2.5rem 0;">
                <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 20px; text-align: center; transition: transform 0.3s ease;"
                     onmouseover="this.style.transform='translateY(-3px)'"
                     onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🌟</div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0 0 0.5rem 0;">Premium Fleet</h3>
                    <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">Top-quality vehicles from trusted brands</p>
                </div>
                
                <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 20px; text-align: center; transition: transform 0.3s ease;"
                     onmouseover="this.style.transform='translateY(-3px)'"
                     onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">⚡</div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0 0 0.5rem 0;">Instant Booking</h3>
                    <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">Quick and hassle-free reservations</p>
                </div>
                
                <div style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); padding: 1.5rem; border-radius: 20px; text-align: center; transition: transform 0.3s ease;"
                     onmouseover="this.style.transform='translateY(-3px)'"
                     onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🛡️</div>
                    <h3 style="font-size: 1.1rem; font-weight: 700; color: #1f2937; margin: 0 0 0.5rem 0;">Reliable Service</h3>
                    <p style="color: #6b7280; margin: 0; font-size: 0.9rem;">24/7 support and maintenance</p>
                </div>
            </div>

            <!-- Action Buttons -->
            @auth
                <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: center; margin-top: 2.5rem;">
                    <a href="{{ url('/bookings') }}" 
                       style="background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 1.25rem 2.5rem; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;"
                       onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 15px 35px rgba(102, 126, 234, 0.4)'"
                       onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 10px 25px rgba(102, 126, 234, 0.3)'">
                        📋 My Bookings
                    </a>
                    <a href="{{ url('/cars') }}" 
                       style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1.25rem 2.5rem; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); transition: all 0.3s ease; display: flex; align-items: center; gap: 0.5rem;"
                       onmouseover="this.style.transform='translateY(-3px) scale(1.02)'; this.style.boxShadow='0 15px 35px rgba(16, 185, 129, 0.4)'"
                       onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 10px 25px rgba(16, 185, 129, 0.3)'">
                        🚗 Browse Cars
                    </a>
                </div>
            @else
                <div style="margin-top: 2.5rem;">
                    <a href="{{ route('register') }}" 
                       style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1.5rem 3rem; border-radius: 50px; text-decoration: none; font-weight: 700; font-size: 1.3rem; box-shadow: 0 15px 35px rgba(16, 185, 129, 0.3); transition: all 0.4s ease; display: inline-flex; align-items: center; gap: 0.75rem;"
                       onmouseover="this.style.transform='translateY(-5px) scale(1.05)'; this.style.boxShadow='0 20px 45px rgba(16, 185, 129, 0.4)'"
                       onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 15px 35px rgba(16, 185, 129, 0.3)'">
                        🚀 Get Started - Register Now
                    </a>
                    
                    <p style="color: #6b7280; margin: 1.5rem 0 0 0; font-size: 0.95rem;">
                        Already have an account? 
                        <a href="{{ route('login') }}" style="color: #667eea; font-weight: 600; text-decoration: none;">
                            Sign in here
                        </a>
                    </p>
                </div>
            @endauth
        </div>

        <!-- Quick Stats Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; max-width: 800px; width: 100%; margin-bottom: 2rem;">
            <div style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 2rem; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: transform 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-5px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 2rem; font-weight: 800; color: #667eea; margin-bottom: 0.5rem;">9+</div>
                <div style="color: #6b7280; font-weight: 600;">Premium Vehicles</div>
            </div>
            
            <div style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 2rem; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: transform 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-5px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 2rem; font-weight: 800; color: #10b981; margin-bottom: 0.5rem;">24/7</div>
                <div style="color: #6b7280; font-weight: 600;">Customer Support</div>
            </div>
            
            <div style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 2rem; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transition: transform 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-5px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 2rem; font-weight: 800; color: #f59e0b; margin-bottom: 0.5rem;">3</div>
                <div style="color: #6b7280; font-weight: 600;">Branches</div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer style="text-align: center; padding: 2rem; margin-top: 2rem;">
        <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 15px; display: inline-block;">
            <p style="color: rgba(255,255,255,0.8); margin: 0; font-weight: 500;">
                &copy; {{ date('Y') }} EasyCar. All rights reserved.
            </p>
        </div>
    </footer>

    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        @media (max-width: 768px) {
            main h1 {
                font-size: 2.5rem !important;
            }
            
            main p {
                font-size: 1.1rem !important;
            }
            
            .feature-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</body>
</html>