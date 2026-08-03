<x-layouts::app :title="__('Inicio')">
    @php
        $canCreateStorefront = $storefrontQuota['can_create'] ?? true;
        $storefrontLimit = $storefrontQuota['limit'] ?? null;
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <flux:heading size="xl">{{ __('Hola, :name', ['name' => auth()->user()->name]) }} 👋</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('Así va tu negocio hoy.') }}
        </flux:text>

        @if ($businesses->isEmpty())
            <x-states.empty
                title="Todavía no tienes ninguna vitrina"
                description="Crea tu Merkamigo en cinco minutos y empieza a recibir contactos por WhatsApp."
            >
                @if ($canCreateStorefront)
                    <flux:button variant="primary" :href="route('emprendedores.crear-vitrina')" wire:navigate>
                        {{ __('Crear mi vitrina') }}
                    </flux:button>
                @endif
            </x-states.empty>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($businesses as $business)
                    <div class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between">
                            <flux:heading>{{ $business->name }}</flux:heading>
                            <flux:badge size="sm" :color="$business->isPublished() ? 'green' : ($business->isSuspended() ? 'red' : 'zinc')">
                                {{ ucfirst($business->status) }}
                            </flux:badge>
                        </div>

                        @if ($business->isSuspended())
                            <div class="rounded-lg border border-red-300 bg-red-50 p-3 text-sm dark:border-red-800 dark:bg-red-950">
                                <div class="font-medium">{{ __('Tu vitrina fue suspendida.') }}</div>
                                <div class="mt-1">{{ $business->suspension_reason }}</div>
                                <div class="mt-1 text-xs text-zinc-500">
                                    {{ __('Si crees que es un error, contáctanos por soporte.') }}
                                    <a href="{{ route('soporte') }}" class="underline" wire:navigate>{{ __('Ir a soporte') }}</a>
                                </div>
                            </div>
                        @endif

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

                        @if ($business->isPublished() && ($metrics = $metricsByBusiness[$business->id] ?? null))
                            <div class="grid grid-cols-2 gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800">
                                <div>
                                    <div class="text-xl font-semibold">{{ $metrics['total_views'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ __('Visitas esta semana') }}</div>
                                </div>
                                <div>
                                    <div class="text-xl font-semibold">{{ $metrics['total_whatsapp_clicks'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ __('Mensajes esta semana') }}</div>
                                </div>
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
                                <flux:button size="sm" variant="ghost" icon="megaphone" :href="route('emprendedores.negocios.copiloto', $business)" wire:navigate>
                                    {{ __('Copiloto de WhatsApp') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($canCreateStorefront)
                <flux:button variant="ghost" class="self-start" :href="route('emprendedores.crear-vitrina')" wire:navigate>
                    {{ __('+ Crear otra vitrina') }}
                </flux:button>
            @elseif ($storefrontLimit)
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Ya alcanzaste el máximo de :count vitrinas para tu plan actual.', ['count' => $storefrontLimit]) }}
                </flux:text>
            @endif
        @endif
    </div>
</x-layouts::app>
