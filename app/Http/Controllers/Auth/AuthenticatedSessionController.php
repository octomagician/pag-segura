<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // 1. Breeze valida correo y contraseña y "loguea" al usuario
        $request->authenticate();

        $user = $request->user();

        // 2. Interceptamos: Si el usuario ya tiene configurado su 2FA...
        if (!empty($user->google2fa_secret)) {
            
            // Lo deslogueamos inmediatamente por seguridad
            Auth::logout(); 
            
            // Guardamos su ID temporalmente en la sesión para saber quién intenta entrar
            $request->session()->put('2fa:user:id', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));
            
            // Guardamos un "timestamp" que expira en 5 minutos
            $request->session()->put('2fa:expires_at', now()->addMinutes(5)->timestamp);

            // Lo mandamos al formulario para pedir el código OTP
            return redirect()->route('2fa.challenge');
        }

        // 3. Si NO tiene 2FA (por ejemplo, acaba de registrarse), lo dejamos pasar normal
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
