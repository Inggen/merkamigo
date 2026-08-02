<x-layouts::auth :title="__('Confirmar contraseña')">
    <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('Confirma tu contraseña')"
            :description="__('Esta es una zona segura. Confirma tu contraseña antes de continuar.')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <x-passkey-verify
            options-route="passkey.confirm-options"
            submit-route="passkey.confirm"
            :label="__('Confirmar con passkey')"
            :loading-label="__('Confirmando...')"
            :separator="__('O confirma con contraseña')"
        />

        <form method="POST" action="{{ route('front.password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('Contraseña')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('Contraseña')"
                viewable
            />

                <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('Confirmar') }}
            </flux:button>
        </form>
    </div>
</x-layouts::auth>
