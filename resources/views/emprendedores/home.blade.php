<x-layouts::app :title="__('Inicio')">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading size="xl">{{ __('Hola, :name', ['name' => auth()->user()->name]) }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('Esta es tu experiencia como Emprendedor en Merkamigo.') }}
        </flux:text>

        @if ($businesses->isEmpty())
            <x-states.empty
                title="Todavía no tienes ninguna vitrina"
                description="Crea tu Merkamigo en cinco minutos y empieza a recibir contactos por WhatsApp."
            >
                <flux:button variant="primary" :href="route('emprendedores.crear-vitrina')" wire:navigate>
                    {{ __('Crear mi vitrina') }}
                </flux:button>
            </x-states.empty>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($businesses as $business)
                    <div class="space-y-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between">
                            <flux:heading>{{ $business->name }}</flux:heading>
                            <flux:badge size="sm" :color="$business->isPublished() ? 'green' : 'zinc'">
                                {{ ucfirst($business->status) }}
                            </flux:badge>
                        </div>

                        @if (! $business->isPublished() && ($missingByBusiness[$business->id] ?? []) !== [])
                            <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm dark:border-amber-800 dark:bg-amber-950">
                                <div class="font-medium">{{ __('Te falta para vender:') }}</div>
                                <ul class="mt-1 list-inside list-disc">
                                    @foreach ($missingByBusiness[$business->id] as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" variant="primary" :href="route('emprendedores.negocios.vitrina', $business)" wire:navigate>
                                {{ __('Editar vitrina') }}
                            </flux:button>
                            <flux:button size="sm" variant="ghost" :href="route('emprendedores.negocios.productos', $business)" wire:navigate>
                                {{ __('Productos') }}
                            </flux:button>
                            @if ($business->isPublished())
                                @if ($business->whatsapp_number)
                                    <flux:button
                                        size="sm"
                                        variant="ghost"
                                        icon="chat-bubble-left-right"
                                        href="https://wa.me/{{ preg_replace('/\D/', '', $business->whatsapp_number) }}"
                                        target="_blank"
                                    >
                                        {{ __('WhatsApp') }}
                                    </flux:button>
                                @endif
                                <flux:button size="sm" variant="ghost" icon="qr-code" :href="route('emprendedores.negocios.compartir', $business)" wire:navigate>
                                    {{ __('Compartir y QR') }}
                                </flux:button>
                                <flux:button size="sm" variant="ghost" icon="chart-bar" :href="route('emprendedores.negocios.metricas', $business)" wire:navigate>
                                    {{ __('Métricas') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <flux:button variant="ghost" class="self-start" :href="route('emprendedores.crear-vitrina')" wire:navigate>
                {{ __('+ Crear otra vitrina') }}
            </flux:button>
        @endif
    </div>
</x-layouts::app>
