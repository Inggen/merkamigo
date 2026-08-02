@php
    $schemaGraph = [
        \App\Support\Seo\SchemaBuilder::breadcrumb([
            ['name' => __('Inicio'), 'url' => route('home')],
            ['name' => __('Soporte')],
        ]),
        \App\Support\Seo\SchemaBuilder::contactPage(config('services.merkamigo.support_whatsapp')),
    ];
@endphp

<x-layouts::public
    :title="__('Soporte')"
    :description="__('Canal de soporte de Merkamigo para ayudarte a crear o completar tu vitrina.')"
    :canonical="route('soporte')"
    page-schema-type="ContactPage"
    :schema-graph="$schemaGraph"
>
    <div class="mx-auto max-w-2xl px-6 py-10 text-center">
        <flux:heading size="xl" class="mb-2">{{ __('¿Necesitas ayuda?') }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Escríbenos y te ayudamos a crear o completar tu vitrina.') }}</flux:subheading>

        @if ($whatsapp = config('services.merkamigo.support_whatsapp'))
            <flux:button
                variant="primary"
                icon="chat-bubble-left-right"
                href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}"
                target="_blank"
            >
                {{ __('Escribir por WhatsApp') }}
            </flux:button>
        @else
            <x-states.empty
                title="{{ __('Canal de soporte en configuración') }}"
                description="{{ __('Todavía no se ha definido el número de WhatsApp de soporte.') }}"
            />
        @endif

        <div class="mt-4">
            <flux:link :href="route('soporte.solicitud.crear')" wire:navigate>
                {{ __('¿Prefieres escribirnos? Envía tu solicitud aquí.') }}
            </flux:link>
        </div>
    </div>
</x-layouts::public>
