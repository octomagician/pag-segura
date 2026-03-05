<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Por favor, confirma el acceso a tu cuenta ingresando el código de 6 dígitos de tu aplicación.') }}
    </div>

    <div class="mb-4 text-center font-bold text-red-600" id="timer-container">
        Tiempo restante: <span id="countdown">05:00</span>
    </div>

    <form method="POST" action="{{ route('2fa.verify') }}">
        @csrf

        <div>
            <x-input-label for="otp" value="{{ __('Código de Autenticación') }}" />
            
            <x-text-input id="otp" 
                class="block mt-1 w-full text-center tracking-widest text-2xl font-mono" 
                type="text" 
                name="otp" 
                required 
                autofocus 
                autocomplete="one-time-code"
                inputmode="numeric" 
                pattern="[0-9]*"
                maxlength="6"
                oninput="this.value = this.value.replace(/[^0-9]/g, '')" />
            
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button class="ms-3" type="submit">
                {{ __('Verificar e Iniciar Sesión') }}
            </x-primary-button>
        </div>
    </form>

    <script>
        // Traemos el timestamp desde Laravel y lo convertimos a milisegundos para JavaScript
        const expiresAt = {{ $expiresAt }} * 1000; 
        const countdownElement = document.getElementById('countdown');

        const updateTimer = setInterval(() => {
            const now = new Date().getTime();
            const distance = expiresAt - now;

            if (distance <= 0) {
                // El tiempo se acabó
                clearInterval(updateTimer);
                countdownElement.innerHTML = "00:00";
                
                // Redirigimos automáticamente al login
                window.location.href = "{{ route('login') }}"; 
            } else {
                // Cálculos matemáticos para minutos y segundos
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                // Formateamos para que siempre tenga dos dígitos (ej. 04:09)
                countdownElement.innerHTML = 
                    (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                    (seconds < 10 ? "0" + seconds : seconds);
            }
        }, 1000); // Se actualiza cada 1000 milisegundos (1 segundo)
    </script>
</x-guest-layout>