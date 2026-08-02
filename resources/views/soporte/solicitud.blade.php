<x-layouts::public :title="__('Solicitud de soporte')">
    <div class="mx-auto max-w-lg px-6 py-10">
        <flux:heading size="xl" class="mb-2">{{ __('Cuéntanos qué necesitas') }}</flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Si prefieres dejar constancia escrita en vez de escribirnos por WhatsApp, usa este formulario y te responderemos pronto.') }}
        </flux:subheading>

        @if (session('status'))
            <div class="mb-6 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('soporte.solicitud.guardar') }}" class="space-y-6">
            @csrf

            <flux:input name="subject" :label="__('Asunto')" :value="old('subject')" required />

            <flux:textarea name="message" :label="__('Mensaje')" rows="5" :value="old('message')" required />

            @guest
                <flux:input
                    name="contact_email"
                    type="email"
                    :label="__('Tu correo (para responderte)')"
                    :value="old('contact_email')"
                    required
                />
            @endguest

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Enviar solicitud') }}
            </flux:button>
        </form>
    </div>
</x-layouts::public>
