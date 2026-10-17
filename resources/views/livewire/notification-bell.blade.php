{{--
    Mismo motivo que `favorite-button.blade.php`: un único <div> raíz es
    obligatorio para que Livewire detecte correctamente el elemento raíz
    del componente.
--}}
<div wire:poll.30s="$refresh">
    <flux:dropdown position="bottom" align="end">
        <button
            type="button"
            class="relative flex size-9 items-center justify-center rounded-xl text-zinc-600 transition hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700"
            aria-label="{{ __('Notificaciones') }}"
            title="{{ __('Notificaciones') }}"
        >
            <flux:icon.bell class="size-5" variant="outline" />
            @if ($this->unreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-xs font-semibold leading-5 text-white">
                    {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
                </span>
            @endif
        </button>

        <flux:menu class="w-80 max-w-[90vw] !p-0 sm:w-96">
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Notificaciones') }}</flux:heading>
                @if ($this->unreadCount > 0)
                    <button type="button" wire:click="markAllRead" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-300">
                        {{ __('Marcar todas como leídas') }}
                    </button>
                @endif
            </div>

            <div class="max-h-96 overflow-y-auto">
                @forelse ($this->notifications as $notification)
                    @php($isMessage = ($notification->data['type'] ?? null) === 'business_message')
                    <div
                        data-notification-state="{{ $notification->read_at ? 'read' : 'unread' }}"
                        class="group flex items-start border-b border-zinc-100 transition last:border-0 dark:border-zinc-800 {{ $notification->read_at ? 'bg-white dark:bg-zinc-900' : 'bg-brand-50/70 dark:bg-brand-500/10' }}"
                    >
                        <button
                            type="button"
                            wire:click="open('{{ $notification->id }}')"
                            wire:loading.attr="disabled"
                            class="flex min-w-0 flex-1 items-start gap-3 px-4 py-3 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800"
                        >
                            <span @class([
                                'mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full',
                                'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-300' => $isMessage,
                                'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300' => ! $isMessage,
                            ])>
                                <flux:icon :name="$isMessage ? 'chat-bubble-left-right' : 'bell'" class="size-4" variant="solid" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span @class([
                                    'block text-sm leading-5 text-zinc-700 dark:text-zinc-200',
                                    'font-semibold text-zinc-900 dark:text-white' => ! $notification->read_at,
                                ])>
                                    {{ $notification->data['message'] ?? __('Novedad') }}
                                </span>
                                <span class="mt-1 flex items-center gap-2 text-xs text-zinc-400">
                                    <span>{{ $notification->created_at->diffForHumans() }}</span>
                                    <span @class([
                                        'rounded-full px-2 py-0.5 font-medium',
                                        'bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-200' => ! $notification->read_at,
                                        'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' => $notification->read_at,
                                    ])>{{ $notification->read_at ? __('Leída') : __('Nueva') }}</span>
                                </span>
                            </span>
                        </button>
                        <button
                            type="button"
                            wire:click="delete('{{ $notification->id }}')"
                            wire:confirm="{{ __('¿Eliminar esta notificación?') }}"
                            class="mr-2 mt-2 flex size-8 shrink-0 items-center justify-center rounded-lg text-zinc-400 opacity-70 transition hover:bg-red-50 hover:text-red-600 group-hover:opacity-100 dark:hover:bg-red-500/10"
                            aria-label="{{ __('Eliminar notificación') }}"
                            title="{{ __('Eliminar notificación') }}"
                        >
                            <flux:icon.trash class="size-4" />
                        </button>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-zinc-400">{{ __('Todavía no tienes novedades.') }}</p>
                @endforelse
            </div>

            <x-push-notification-settings compact />

            <div class="flex items-center justify-between gap-2 border-t border-zinc-100 px-4 py-2.5 dark:border-zinc-700">
                <flux:link :href="route('messages.index')" wire:navigate class="text-xs font-medium">{{ __('Ver mensajes') }}</flux:link>
                <flux:link :href="route('clientes.actividad')" wire:navigate class="text-xs font-medium">{{ __('Ver toda la actividad') }}</flux:link>
            </div>
        </flux:menu>
    </flux:dropdown>
</div>
