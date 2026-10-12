@props(['compact' => false])

@php
    $configured = collect(config('services.fcm.web'))->except('storage_bucket')->every(fn ($value) => filled($value));
@endphp

@if ($configured)
<section
    x-data="{
        status: window.merkamigoPushNeedsInstall?.() ? 'install-required' : (window.merkamigoPushStatus?.status ?? (window.Notification?.permission === 'denied' ? 'denied' : 'default')),
        error: window.merkamigoPushStatus?.error ?? '',
        loading: false,
        async enable() {
            this.loading = true;
            this.error = '';
            try {
                const result = await window.enableMerkamigoPush();
                this.status = result.status;
            } catch (error) {
                this.status = 'error';
                this.error = error.message || @js(__('No se pudieron activar las notificaciones.'));
            } finally {
                this.loading = false;
            }
        },
    }"
    x-on:merkamigo-push-status.window="status = $event.detail.status; error = $event.detail.error"
    x-cloak
    x-show="status !== 'granted'"
    {{ $attributes->class($compact ? 'border-t border-zinc-100 px-4 py-3 dark:border-zinc-700' : 'rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900') }}
>
    <div class="flex gap-3 {{ $compact ? 'items-center justify-between' : 'flex-col sm:flex-row sm:items-center sm:justify-between' }}">
        <div>
            <flux:heading :size="$compact ? 'sm' : 'lg'">{{ $compact ? __('Avisos del navegador') : __('Notificaciones en este dispositivo') }}</flux:heading>
            <flux:text class="mt-1 {{ $compact ? 'text-xs' : 'text-sm' }}">
                {{ $compact ? __('Recibe mensajes aunque no tengas abierta esta página.') : __('Recibe avisos de mensajes y novedades aunque Merkamigo esté en segundo plano.') }}
            </flux:text>
        </div>

        <flux:button
            type="button"
            variant="primary"
            icon="bell-alert"
            size="sm"
            x-on:click="enable"
            x-show="status !== 'install-required'"
            x-bind:disabled="loading || status === 'granted' || status === 'denied'"
        >
            <span x-show="status === 'granted'">{{ __('Activados') }}</span>
            <span x-show="status === 'denied'">{{ __('Bloqueadas en el navegador') }}</span>
            <span x-show="status !== 'granted' && status !== 'denied'" x-text="loading ? @js(__('Activando…')) : @js(__('Activar notificaciones'))"></span>
        </flux:button>
    </div>
    <p x-show="error" x-cloak x-text="error" role="alert" class="mt-2 text-sm text-red-600"></p>
    <p x-show="status === 'install-required'" x-cloak class="mt-2 text-sm text-zinc-500">{{ __('En iPhone, abre Merkamigo en Safari, pulsa Compartir → Agregar a pantalla de inicio y abre la aplicación desde su icono. Luego activa las notificaciones. Requiere iOS 16.4 o posterior.') }}</p>
    <p x-show="status === 'unavailable'" x-cloak class="mt-2 text-sm text-zinc-500">{{ __('Este navegador no admite push. En iPhone, abre Merkamigo desde la aplicación instalada en la pantalla de inicio.') }}</p>
</section>
@endif
