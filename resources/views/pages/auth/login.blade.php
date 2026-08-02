<x-layouts::auth :title="__('Iniciar sesión')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Ingresa a tu cuenta')" :description="__('Escribe tu correo o teléfono y tu contraseña para continuar')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify />

        <form method="POST" action="{{ route('front.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="email"
                :label="__('Correo o teléfono')"
                :value="old('email')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="correo@ejemplo.com"
            />

            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Contraseña')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Contraseña')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('¿Olvidaste tu contraseña?') }}
                    </flux:link>
                @endif
            </div>

            <flux:checkbox name="remember" :label="__('Recordarme')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Ingresar') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('¿No tienes una cuenta?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Crear cuenta') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
