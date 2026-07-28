<x-layouts::public :title="$title">
    <div class="mx-auto max-w-lg px-6 py-10">
        <flux:heading size="xl" class="mb-2">{{ $title }}</flux:heading>
        <flux:subheading class="mb-6">
            {{ __('Cuéntanos qué está mal. Un moderador revisará tu reporte.') }}
        </flux:subheading>

        <form method="POST" action="{{ $actionUrl }}" class="space-y-6">
            @csrf

            <flux:select name="reason" :label="__('Motivo')" required>
                <flux:select.option value="contenido_inapropiado">{{ __('Contenido inapropiado') }}</flux:select.option>
                <flux:select.option value="informacion_falsa">{{ __('Información falsa o engañosa') }}</flux:select.option>
                <flux:select.option value="spam">{{ __('Spam o suplantación') }}</flux:select.option>
                <flux:select.option value="otro">{{ __('Otro') }}</flux:select.option>
            </flux:select>

            <flux:textarea name="details" :label="__('Detalles (opcional)')" rows="4" :value="old('details')" />

            <flux:input
                name="reporter_email"
                type="email"
                :label="__('Tu correo (opcional, por si necesitamos más información)')"
                :value="old('reporter_email')"
            />

            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Enviar reporte') }}
            </flux:button>
        </form>
    </div>
</x-layouts::public>
