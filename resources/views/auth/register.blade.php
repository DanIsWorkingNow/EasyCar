<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>EasyCar - Register</title>
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
    <header style="display: flex; justify-content: space-between; align-items: center; padding: 2rem; position: relative; z-index: 10;">
        <!-- Logo/Brand -->
        <div>
            <a href="{{ url('/') }}" style="color: white; text-decoration: none; font-size: 1.5rem; font-weight: 800;">
                🚗 EasyCar
            </a>
        </div>

        <!-- Navigation Links -->
        <div style="display: flex; gap: 1.5rem; align-items: center;">
            <a href="{{ url('/') }}" 
               style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.2);"
               onmouseover="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(-2px)'"
               onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)'">
                🏠 Home
            </a>
            
            @if (Route::has('login'))
                <a href="{{ route('login') }}" 
                   style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; border: 1px solid rgba(255,255,255,0.3);"
                   onmouseover="this.style.background='rgba(255,255,255,0.3)'; this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.2)'; this.style.transform='translateY(0)'">
                🔑 Login
                </a>
            @endif
        </div>
    </header>

    <!-- Main Content -->
    <main style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 2rem; margin-top: -2rem;">
        
        <!-- Register Form Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 30px; padding: 4rem 3rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); text-align: center; max-width: 550px; width: 100%; position: relative; overflow: hidden;">
            
            <!-- Decorative Elements -->
            <div style="position: absolute; top: -40px; right: -40px; width: 80px; height: 80px; background: linear-gradient(45deg, #10b981, #059669); border-radius: 50%; opacity: 0.1; animation: float 6s ease-in-out infinite;"></div>
            <div style="position: absolute; bottom: -30px; left: -30px; width: 60px; height: 60px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1; animation: float 4s ease-in-out infinite reverse;"></div>
            
            <!-- Heading -->
            <div style="margin-bottom: 2rem;">
                <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #10b981, #059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0 0 0.5rem 0; line-height: 1.2;">
                    📝 Create Account
                </h1>
                <p style="color: #6b7280; font-size: 1.1rem; margin: 0;">
                    Join EasyCar and start your journey
                </p>
            </div>

            <!-- Register Form -->
            <form method="POST" action="{{ route('register') }}" style="text-align: left;">
                @csrf

                <!-- Name Field -->
                <div style="margin-bottom: 1.5rem;">
                    <label for="name" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        {{ __('Full Name') }}
                    </label>
                    <input id="name" type="text" 
                           class="form-control @error('name') is-invalid @enderror" 
                           name="name" 
                           value="{{ old('name') }}" 
                           required 
                           autocomplete="name" 
                           autofocus
                           style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1rem; transition: all 0.3s ease; background: #f9fafb;"
                           onfocus="this.style.borderColor='#10b981'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)'"
                           onblur="this.style.borderColor='#e5e7eb'; this.style.background='#f9fafb'; this.style.boxShadow='none'">

                    @error('name')
                        <div style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; font-weight: 500;">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Email Field -->
                <div style="margin-bottom: 1.5rem;">
                    <label for="email" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        {{ __('Email Address') }}
                    </label>
                    <input id="email" type="email" 
                           class="form-control @error('email') is-invalid @enderror" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autocomplete="email"
                           style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1rem; transition: all 0.3s ease; background: #f9fafb;"
                           onfocus="this.style.borderColor='#10b981'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)'"
                           onblur="this.style.borderColor='#e5e7eb'; this.style.background='#f9fafb'; this.style.boxShadow='none'">

                    @error('email')
                        <div style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; font-weight: 500;">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Password Field -->
                <div style="margin-bottom: 1.5rem;">
                    <label for="password" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        {{ __('Password') }}
                    </label>
                    <input id="password" type="password" 
                           class="form-control @error('password') is-invalid @enderror" 
                           name="password" 
                           required 
                           autocomplete="new-password"
                           style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1rem; transition: all 0.3s ease; background: #f9fafb;"
                           onfocus="this.style.borderColor='#10b981'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)'"
                           onblur="this.style.borderColor='#e5e7eb'; this.style.background='#f9fafb'; this.style.boxShadow='none'">

                    @error('password')
                        <div style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; font-weight: 500;">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Confirm Password Field -->
                <div style="margin-bottom: 2rem;">
                    <label for="password-confirm" style="display: block; color: #374151; font-weight: 600; margin-bottom: 0.5rem; font-size: 0.95rem;">
                        {{ __('Confirm Password') }}
                    </label>
                    <input id="password-confirm" type="password" 
                           class="form-control" 
                           name="password_confirmation" 
                           required 
                           autocomplete="new-password"
                           style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1rem; transition: all 0.3s ease; background: #f9fafb;"
                           onfocus="this.style.borderColor='#10b981'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)'"
                           onblur="this.style.borderColor='#e5e7eb'; this.style.background='#f9fafb'; this.style.boxShadow='none'">
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        style="width: 100%; background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 1rem 2rem; border: none; border-radius: 15px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3); transition: all 0.3s ease; cursor: pointer; margin-bottom: 1.5rem;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 35px rgba(16, 185, 129, 0.4)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(16, 185, 129, 0.3)'">
                    {{ __('Create Account') }}
                </button>
            </form>

            <!-- Login Link -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; text-align: center;">
                <p style="color: #6b7280; margin: 0; font-size: 0.95rem;">
                    Already have an account? 
                    @if (Route::has('login'))
                        <a href="{{ route('login') }}" 
                           style="color: #667eea; font-weight: 600; text-decoration: none; transition: color 0.3s ease;"
                           onmouseover="this.style.color='#4f46e5'"
                           onmouseout="this.style.color='#667eea'">
                            Sign in here
                        </a>
                    @endif
                </p>
            </div>

            <!-- Terms Notice -->
            <div style="margin-top: 1.5rem; text-align: center;">
                <p style="color: #9ca3af; font-size: 0.85rem; margin: 0; line-height: 1.4;">
                    By creating an account, you agree to our Terms of Service and Privacy Policy
                </p>
            </div>
        </div>

        <!-- Features Preview -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; max-width: 550px; width: 100%; margin-top: 2rem;">
            <div style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 15px; text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,0.1); transition: transform 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-3px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🚗</div>
                <div style="color: #6b7280; font-weight: 600; font-size: 0.9rem;">Premium Cars</div>
            </div>
            
            <div style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 15px; text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,0.1); transition: transform 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-3px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">⚡</div>
                <div style="color: #6b7280; font-weight: 600; font-size: 0.9rem;">Fast Booking</div>
            </div>
            
            <div style="background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 1.5rem; border-radius: 15px; text-align: center; box-shadow: 0 8px 20px rgba(0,0,0,0.1); transition: transform 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-3px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🛡️</div>
                <div style="color: #6b7280; font-weight: 600; font-size: 0.9rem;">24/7 Support</div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer style="text-align: center; padding: 2rem; margin-top: 2rem;">
        <div style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 1rem; border-radius: 15px; display: inline-block;">
            <p style="color: rgba(255,255,255,0.8); margin: 0; font-weight: 500; font-size: 0.9rem;">
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
                font-size: 2rem !important;
            }
            
            header {
                flex-direction: column;
                gap: 1rem;
                padding: 1.5rem 1rem;
            }
            
            main {
                padding: 0 1rem;
            }
            
            .register-form {
                padding: 2.5rem 2rem !important;
            }
            
            .features-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
            }
        }
    </style>
</body>
</html>