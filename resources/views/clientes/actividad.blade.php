<x-layouts::app :title="__('Actividad')">
    <div class="mx-auto flex h-full w-full max-w-4xl flex-1 flex-col gap-6 px-4 py-8 sm:px-6">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Actividad') }}</flux:heading>
            <flux:button size="sm" variant="ghost" icon="shopping-bag" :href="route('clientes.pedidos')" wire:navigate>
                {{ __('Mis pedidos') }}
            </flux:button>
        </div>

        <x-push-notification-settings />

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
                    <div data-notification-state="{{ $notification->read_at ? 'read' : 'unread' }}" class="flex items-start justify-between gap-4 rounded-2xl border p-4 {{ $notification->read_at ? 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' : 'border-brand-200 bg-brand-50 dark:border-brand-900 dark:bg-brand-950' }}">
                        <div>
                            <flux:text class="font-medium">{{ $notification->data['message'] ?? __('Novedad') }}</flux:text>
                            <div class="mt-1 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $notification->read_at ? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' : 'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200' }}">
                                    {{ $notification->read_at ? __('Leída') : __('Nueva') }}
                                </span>
                            </div>

                            @if (! empty($notification->data['url']))
                                <div class="mt-2">
                                    <flux:link :href="$notification->data['url']" wire:navigate>{{ $notification->data['action_label'] ?? __('Ver detalle') }}</flux:link>
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 items-center gap-1">
                            @unless ($notification->read_at)
                                <form method="POST" action="{{ route('clientes.actividad.leida', $notification->id) }}">
                                    @csrf
                                    <flux:button size="sm" variant="ghost" type="submit">{{ __('Marcar como leída') }}</flux:button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('clientes.actividad.eliminar', $notification->id) }}">
                                @csrf
                                @method('DELETE')
                                <flux:button size="sm" variant="ghost" icon="trash" type="submit" aria-label="{{ __('Eliminar notificación') }}" title="{{ __('Eliminar notificación') }}" />
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-layouts::app>
