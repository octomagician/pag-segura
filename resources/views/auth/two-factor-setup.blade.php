<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Configura la autenticación de dos factores. Escanea este código QR con la app de Google Authenticator e ingresa el código generado para confirmar.') }}
    </div>

    <div class="flex justify-center mb-4">
        {!! $qrCodeSvg !!}
    </div>

    <div class="mb-4 text-center text-sm text-gray-600 dark:text-gray-400">
        <p>Si no puedes escanearlo, ingresa esta llave manualmente: <br> <strong>{{ $secret }}</strong></p>
    </div>

    <form method="POST" action="{{ route('2fa.confirm') }}">
        @csrf

        <div>
            <x-input-label for="otp" value="{{ __('Código de 6 dígitos') }}" />
            <x-text-input id="otp" class="block mt-1 w-full text-center tracking-widest" type="text" name="otp" required autofocus autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3">
                {{ __('Confirmar y Activar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>