<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8) // Mínimo 8 caracteres
                ->letters()         // Debe contener al menos una letra
                ->mixedCase()       // Debe contener mayúsculas y minúsculas
                ->numbers()         // Debe contener al menos un número
                ->symbols()         // Debe contener al menos un símbolo (ej. ! @ # $ %)
                ->uncompromised();  // ¡Magia pura! Revisa si la contraseña ha sido filtrada en hackeos públicos de internet.
        });
    }
}
