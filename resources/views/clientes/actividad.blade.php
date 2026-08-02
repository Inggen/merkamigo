<x-layouts::app :title="__('Actividad')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Actividad') }}</flux:heading>
            <flux:button size="sm" variant="ghost" icon="shopping-bag" :href="route('clientes.pedidos')" wire:navigate>
                {{ __('Mis pedidos') }}
            </flux:button>
        </div>

        @if ($recentlyViewed->isNotEmpty())
            <div>
                <flux:subheading class="mb-3">{{ __('Vistos recientemente') }}</flux:subheading>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($recentlyViewed as $entry)
                        @if ($entry->business)
                            <x-business-card :business="$entry->business" />
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        @if ($notifications->isEmpty())
            <x-states.empty
                :title="__('Todavía no tienes novedades')"
                :description="__('Aquí verás cuando un negocio responda a tus solicitudes en \'Pídelo en Merkamigo\'.')"
            />
        @else
            <div class="space-y-3">
                @foreach ($notifications as $notification)
                    <div class="flex items-start justify-between gap-4 rounded-2xl border p-4 {{ $notification->read_at ? 'border-zinc-200 dark:border-zinc-700' : 'border-brand-200 bg-brand-50 dark:border-brand-900 dark:bg-brand-950' }}">
                        <div>
                            <flux:text class="font-medium">{{ $notification->data['message'] ?? __('Novedad') }}</flux:text>
                            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $notification->created_at->diffForHumans() }}
                            </flux:text>

                            @if (! empty($notification->data['url']))
                                <div class="mt-2">
                                    <flux:link :href="$notification->data['url']" wire:navigate>{{ __('Ver solicitud') }}</flux:link>
                                </div>
                            @endif
                        </div>

                        @unless ($notification->read_at)
                            <form method="POST" action="{{ route('clientes.actividad.leida', $notification->id) }}">
                                @csrf
                                <flux:button size="sm" variant="ghost" type="submit">{{ __('Marcar como leída') }}</flux:button>
                            </form>
                        @endunless
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts::app>
