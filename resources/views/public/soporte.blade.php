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
        <h1 class="mb-2 text-2xl font-semibold tracking-tight text-carbon dark:text-white">{{ __('Soporte de Merkamigo') }}</h1>
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

    {{--
        Pedido del usuario: señalar el chatbot IA (esquina inferior
        derecha, `x-storefront-chat-widget` en `layouts/public.blade.php`)
        para que quede claro que ahí también se puede preguntar lo que
        sea — sobre todo útil en esta página, donde el WhatsApp de
        soporte puede no estar configurado todavía. `fixed` (no dentro
        del contenido centrado) para que la flecha quede anclada al
        mismo punto de la pantalla que el widget, sin importar el
        scroll. Se desvanece sola para no estorbar después de notarse.
    --}}
    <div
        x-data="{ show: true }"
        x-init="setTimeout(() => show = false, 10000)"
        x-show="show"
        x-transition
        x-cloak
        class="pointer-events-none fixed bottom-36 right-6 z-30 hidden flex-col items-end gap-1 sm:flex md:bottom-40 md:right-14"
    >
        <div class="max-w-[12rem] rounded-2xl rounded-br-sm bg-white px-3 py-2 text-right text-sm font-medium leading-5 text-zinc-700 shadow-lg dark:bg-zinc-800 dark:text-zinc-200">
            {{ __('¿Tienes dudas? Pregúntale lo que quieras a nuestro asistente') }}
        </div>
        <flux:icon.arrow-down-right class="size-10 animate-bounce text-red-600" variant="solid" />
    </div>
</x-layouts::public>
