<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    // Mostrar la vista con el QR
    public function show(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();

        // Generamos una llave secreta temporal y la guardamos en la sesión
        // (No la guardamos en la BD hasta que el usuario confirme que funciona)
        $secret = $request->session()->get('google2fa_secret_temp');
        if (!$secret) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('google2fa_secret_temp', $secret);
        }

        // Generamos la URL que lee la app de Google Authenticator
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'), // Nombre de tu app
            $user->email,       // Correo del usuario
            $secret             // La llave secreta
        );

        // Convertimos esa URL en una imagen SVG del Código QR
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = $writer->writeString($qrCodeUrl);

        // Retornamos la vista pasándole el QR y el Secreto en texto (por si la cámara falla)
        return view('auth.two-factor-setup', [
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $secret
        ]);
    }

    // Validar el código que ingrese el usuario
    public function confirm(Request $request)
    {
        // Validamos que envíe exactamente 6 números
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        $user = $request->user();
        $google2fa = new Google2FA();
        $secret = $request->session()->get('google2fa_secret_temp');

        // Verificamos si el código coincide con la llave temporal
        $valid = $google2fa->verifyKey($secret, $request->otp);

        if ($valid) {
            // ¡Es correcto! Guardamos la llave permanentemente en la base de datos
            $user->forceFill([
                'google2fa_secret' => $secret,
            ])->save();

            // Limpiamos la sesión temporal
            $request->session()->forget('google2fa_secret_temp');

            // Lo mandamos al dashboard
            return redirect()->route('dashboard')->with('status', '2FA configurado correctamente.');
        }

        // Si se equivocó de número, lo regresamos con un error
        return back()->withErrors(['otp' => 'El código es incorrecto. Inténtalo de nuevo.']);
    }

    // Mostrar el formulario para pedir el código en el login
    public function challenge(Request $request)
    {
        // Si no hay un ID temporal en la sesión, significa que alguien quiso entrar aquí por error
        if (!$request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        // Rescatamos la variable de la sesión
        $expiresAt = $request->session()->get('2fa:expires_at');

        // Validamos si ya pasaron los 5 minutos
        if (now()->timestamp > $request->session()->get('2fa:expires_at')) {
            // Borramos los datos temporales
            $request->session()->forget(['2fa:user:id', '2fa:remember', '2fa:expires_at']);
            
            // Lo regresamos al login con un mensaje de error
            return redirect()->route('login')->withErrors(['email' => 'Time for enter verification code was done. Please login again.']);
        }

        // Pasamos la variable $expiresAt a la vista
        return view('auth.two-factor-challenge', [
            'expiresAt' => $expiresAt
        ]);
    }

    // Validar el código y loguear definitivamente al usuario
    public function verify(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ]);

        // Validamos si ya pasaron los 5 minutos ANTES de checar la base de datos
        if (now()->timestamp > $request->session()->get('2fa:expires_at')) {
            $request->session()->forget(['2fa:user:id', '2fa:remember', '2fa:expires_at']);
            return redirect()->route('login')->withErrors(['email' => 'Time for enter verification code was done. Please login again.']);
        }

        $userId = $request->session()->get('2fa:user:id');
        $user = User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

        if ($valid) {
            // ¡Código correcto! Lo logueamos oficialmente
            Auth::login($user, $request->session()->get('2fa:remember', false));
            
            // Limpiamos la basura temporal de la sesión
            $request->session()->forget(['2fa:user:id', '2fa:remember']);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Si falló, lo regresamos con un error
        return back()->withErrors(['otp' => 'El código es incorrecto. Inténtalo de nuevo.']);
    }
}