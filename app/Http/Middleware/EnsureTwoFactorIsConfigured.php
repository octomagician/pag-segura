<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsConfigured
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Verificamos si el usuario está logueado y NO tiene el 2FA configurado
        if ($user && empty($user->google2fa_secret)) {
            
            // 2. Para evitar un "bucle infinito" de redirecciones, 
            // permitimos que pase SI YA ESTÁ en las rutas de configuración o si está cerrando sesión
            if (! $request->routeIs('2fa.setup') && ! $request->routeIs('2fa.confirm') && ! $request->routeIs('logout')) {
                
                // 3. Lo forzamos a ir a la vista del QR
                return redirect()->route('2fa.setup');
            }
        }

        // Si todo está bien (ya tiene su código), lo dejamos pasar a donde quería ir
        return $next($request);
    }
}
