@php
    $configured = collect(config('services.fcm.web'))->except('storage_bucket')->every(fn ($value) => filled($value));
@endphp

@if ($configured)
<section
    x-data="{
        status: window.Notification?.permission ?? 'unavailable',
        loading: false,
        async enable() {
            this.loading = true;
            try {
                const result = await window.enableMerkamigoPush();
                this.status = result.status;
            } catch (_) {
                this.status = 'error';
            } finally {
                this.loading = false;
            }
        },
    }"
    {{ $attributes->class('rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900') }}
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="lg">{{ __('Notificaciones en este dispositivo') }}</flux:heading>
            <flux:text class="mt-1 text-sm">
                {{ __('Recibe avisos de nuevas publicaciones y reels aunque Merkamigo esté en segundo plano.') }}
            </flux:text>
        </div>

        <flux:button
            type="button"
            variant="primary"
            icon="bell-alert"
            x-on:click="enable"
            x-bind:disabled="loading || status === 'granted' || status === 'denied'"
        >
            <span x-show="status === 'granted'">{{ __('Activadas') }}</span>
            <span x-show="status === 'denied'">{{ __('Bloqueadas en el navegador') }}</span>
            <span x-show="status !== 'granted' && status !== 'denied'" x-text="loading ? @js(__('Activando…')) : @js(__('Activar notificaciones'))"></span>
        </flux:button>
    </div>
</section>
@endif
