@php
    $pushAvailable = ! auth()->check() || collect(config('services.fcm.web'))->except('storage_bucket')->every(fn ($value) => filled($value));
@endphp
<div class="app-install-placement" x-data="merkamigoAppInstall()" x-bind:style="{ '--app-install-header-height': ($store.mobileChrome?.headerVisible === false ? 0 : headerHeight) + 'px' }" x-on:merkamigo-install-status.window="sync()" x-on:merkamigo-push-status.window="pushStatus = $event.detail.status; error = $event.detail.error || ''">
    <aside x-cloak x-show="!installed || (@js($pushAvailable) && canEnablePush)" class="app-install-actions" aria-label="{{ __('Instalación y notificaciones') }}">
        <button type="button" x-show="!installed" x-on:click="install()" x-bind:disabled="busy || !canInstall" class="app-install-button"><flux:icon.arrow-down-tray class="size-4 shrink-0" /> {{ __('Instalar app') }}</button>
        @auth
            @if (collect(config('services.fcm.web'))->except('storage_bucket')->every(fn ($value) => filled($value)))
                <button type="button" x-show="canEnablePush" x-on:click="enablePush()" x-bind:disabled="busy" class="app-install-button app-install-notifications"><flux:icon.bell-alert class="size-4 shrink-0" /><span x-text="busy ? @js(__('Un momento…')) : @js(__('Activar notificaciones'))"></span></button>
            @endif
        @else
            <button type="button" x-show="canEnablePush" x-bind:disabled="busy" x-on:click="window.location.assign(@js(route('login')))" class="app-install-button app-install-notifications"><flux:icon.bell-alert class="size-4 shrink-0" /> {{ __('Activar notificaciones') }}</button>
        @endauth
        <p x-show="error" x-text="error" role="alert" class="text-xs text-red-700"></p>
    </aside>
</div>
