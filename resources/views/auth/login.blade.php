<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>EasyCar - Login</title>
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
            
            @if (Route::has('register'))
                <a href="{{ route('register') }}" 
                   style="background: linear-gradient(45deg, #10b981, #059669); color: white; padding: 0.75rem 1.5rem; border-radius: 25px; text-decoration: none; font-weight: 600; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(16, 185, 129, 0.4)'"
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(16, 185, 129, 0.3)'">
                📝 Register
                </a>
            @endif
        </div>
    </header>

    <!-- Main Content -->
    <main style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 0 2rem; margin-top: -2rem;">
        
        <!-- Login Form Section -->
        <div style="background: rgba(255,255,255,0.95); backdrop-filter: blur(15px); border-radius: 30px; padding: 4rem 3rem; box-shadow: 0 25px 50px rgba(0,0,0,0.15); text-align: center; max-width: 500px; width: 100%; position: relative; overflow: hidden;">
            
            <!-- Decorative Elements -->
            <div style="position: absolute; top: -30px; right: -30px; width: 60px; height: 60px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.1; animation: float 6s ease-in-out infinite;"></div>
            <div style="position: absolute; bottom: -20px; left: -20px; width: 40px; height: 40px; background: linear-gradient(45deg, #10b981, #059669); border-radius: 50%; opacity: 0.1; animation: float 4s ease-in-out infinite reverse;"></div>
            
            <!-- Heading -->
            <div style="margin-bottom: 2rem;">
                <h1 style="font-size: 2.5rem; font-weight: 800; background: linear-gradient(45deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin: 0 0 0.5rem 0; line-height: 1.2;">
                    🔑 Welcome Back
                </h1>
                <p style="color: #6b7280; font-size: 1.1rem; margin: 0;">
                    Sign in to your EasyCar account
                </p>
            </div>

            <!-- Login Form -->
            <form method="POST" action="{{ route('login') }}" style="text-align: left;">
                @csrf

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
                           autofocus
                           style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1rem; transition: all 0.3s ease; background: #f9fafb;"
                           onfocus="this.style.borderColor='#667eea'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
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
                           autocomplete="current-password"
                           style="width: 100%; padding: 1rem 1.25rem; border: 2px solid #e5e7eb; border-radius: 15px; font-size: 1rem; transition: all 0.3s ease; background: #f9fafb;"
                           onfocus="this.style.borderColor='#667eea'; this.style.background='white'; this.style.boxShadow='0 0 0 3px rgba(102, 126, 234, 0.1)'"
                           onblur="this.style.borderColor='#e5e7eb'; this.style.background='#f9fafb'; this.style.boxShadow='none'">

                    @error('password')
                        <div style="color: #ef4444; font-size: 0.875rem; margin-top: 0.5rem; font-weight: 500;">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Remember Me Checkbox -->
                <div style="margin-bottom: 2rem; display: flex; align-items: center; gap: 0.75rem;">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                           {{ old('remember') ? 'checked' : '' }}
                           style="width: 18px; height: 18px; accent-color: #667eea;">
                    <label for="remember" style="color: #6b7280; font-size: 0.95rem; font-weight: 500;">
                        {{ __('Remember Me') }}
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        style="width: 100%; background: linear-gradient(45deg, #667eea, #764ba2); color: white; padding: 1rem 2rem; border: none; border-radius: 15px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); transition: all 0.3s ease; cursor: pointer; margin-bottom: 1.5rem;"
                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 15px 35px rgba(102, 126, 234, 0.4)'"
                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 10px 25px rgba(102, 126, 234, 0.3)'">
                    {{ __('Login') }}
                </button>

                <!-- Forgot Password Link -->
                @if (Route::has('password.request'))
                    <div style="text-align: center;">
                        <a href="{{ route('password.request') }}" 
                           style="color: #667eea; text-decoration: none; font-weight: 600; font-size: 0.95rem; transition: color 0.3s ease;"
                           onmouseover="this.style.color='#4f46e5'"
                           onmouseout="this.style.color='#667eea'">
                            {{ __('Forgot Your Password?') }}
                        </a>
                    </div>
                @endif
            </form>

            <!-- Register Link -->
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; text-align: center;">
                <p style="color: #6b7280; margin: 0; font-size: 0.95rem;">
                    Don't have an account? 
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                           style="color: #10b981; font-weight: 600; text-decoration: none; transition: color 0.3s ease;"
                           onmouseover="this.style.color='#059669'"
                           onmouseout="this.style.color='#10b981'">
                            Register here
                        </a>
                    @endif
                </p>
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
            
            .login-form {
                padding: 2.5rem 2rem !important;
            }
        }
    </style>
</body>
</html>