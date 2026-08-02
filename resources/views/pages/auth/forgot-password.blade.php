<x-layouts::auth :title="__('Recuperar contraseña')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Recupera tu contraseña')" :description="__('Escribe tu correo y te enviaremos un enlace para restablecerla')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('front.password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Correo electrónico')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('Enviar enlace de recuperación') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('O vuelve a') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('iniciar sesión') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
