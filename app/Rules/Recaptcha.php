<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Hacemos una petición POST a la API oficial de Google
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => env('RECAPTCHA_SECRET_KEY'),
            'response' => $value,
            'remoteip' => request()->ip(), // Opcional, pero recomendado
        ]);

        // Si Google dice que no fue exitoso, fallamos la validación
        if (! $response->json('success')) {
            $fail('La validación de reCAPTCHA ha fallado. Por favor, inténtalo de nuevo.');
        }
    }
}