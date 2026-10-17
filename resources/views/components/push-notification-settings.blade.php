@props(['compact' => false])

@if (collect(config('services.fcm.web'))->except('storage_bucket')->every(fn ($value) => filled($value)))
    <div x-data="merkamigoAppInstall()" x-on:merkamigo-push-status.window="pushStatus = $event.detail.status" x-cloak x-show="canEnablePush" {{ $attributes->class($compact ? 'border-t border-zinc-100 px-4 py-3 dark:border-zinc-700' : '') }}>
        <flux:button type="button" variant="primary" icon="bell-alert" size="sm" x-on:click="enablePush()" x-bind:disabled="busy">
            <span x-text="busy ? @js(__('Activando…')) : @js(__('Activar notificaciones'))"></span>
        </flux:button>
    </div>
@endif
