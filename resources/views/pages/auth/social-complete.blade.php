<x-layouts::auth :title="__('Completa tu registro')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Completa tu registro')" :description="__('Solo falta tu teléfono y aceptar los documentos legales de Merkamigo')" />

        <form method="POST" action="{{ route('auth.social.store') }}" class="flex flex-col gap-6">
            @csrf
            <flux:input name="name" :label="__('Nombre')" :value="old('name', $profile['name'])" required autocomplete="name" />
            <flux:input name="email" :label="__('Correo electrónico')" :value="$profile['email']" type="email" readonly />
            <flux:input name="phone" :label="__('Teléfono')" :value="old('phone')" type="tel" required autofocus autocomplete="tel" placeholder="+57 300 000 0000" />

            <label for="terms" class="flex items-start gap-3 rounded-xl border border-zinc-200 px-3 py-3 text-sm text-zinc-700 dark:border-zinc-700 dark:text-zinc-300">
                <flux:checkbox id="terms" name="terms" value="1" :checked="old('terms')" required :label="null" class="mt-0.5 shrink-0" />
                <span class="leading-6">
                    {{ __('Declaro que leí y acepto los') }}
                    <a href="{{ route('terminos') }}" target="_blank" class="font-medium text-brand-600 underline">{{ __('términos y condiciones') }}</a>
                    {{ __('y la') }}
                    <a href="{{ route('privacidad') }}" target="_blank" class="font-medium text-brand-600 underline">{{ __('política de privacidad y habeas data') }}</a>.
                </span>
            </label>

            <flux:button type="submit" variant="primary" class="w-full">{{ __('Crear mi cuenta') }}</flux:button>
        </form>
    </div>
</x-layouts::auth>
