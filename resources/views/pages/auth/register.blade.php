<x-layouts::auth :title="__('Crear cuenta')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Crea tu cuenta')" :description="__('Completa tus datos para empezar a usar Merkamigo')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('front.register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('Nombre')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Nombre completo')"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Correo electrónico (opcional si agregas un teléfono)')"
                :value="old('email')"
                type="email"
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Phone -->
            <flux:input
                name="phone"
                :label="__('Teléfono (opcional si agregas un correo)')"
                :value="old('phone')"
                type="tel"
                autocomplete="tel"
                placeholder="+57 300 000 0000"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Contraseña')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('Confirmar contraseña')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Confirmar contraseña')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <label for="terms" class="flex items-start gap-3 rounded-xl border border-zinc-200 px-3 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-300">
                <flux:checkbox
                    id="terms"
                    name="terms"
                    value="1"
                    :checked="old('terms')"
                    required
                    :label="null"
                    class="mt-0.5 shrink-0"
                />
                <span class="leading-6">
                    {{ __('Declaro que leí y acepto los') }}
                    <a href="{{ route('terminos') }}" target="_blank" class="font-medium text-brand-600 underline">
                        {{ __('términos y condiciones') }}
                    </a>
                    {{ __('y la') }}
                    <a href="{{ route('privacidad') }}" target="_blank" class="font-medium text-brand-600 underline">
                        {{ __('política de privacidad y habeas data') }}
                    </a>
                    {{ __('de Merkamigo.') }}
                </span>
            </label>

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('Crear cuenta') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('¿Ya tienes una cuenta?') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('Iniciar sesión') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
