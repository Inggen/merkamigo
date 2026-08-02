@props([
    'near' => null,
    'compact' => false,
])

{{--
    "Cerca de mí" (1.1.1/1.5 del TODO): comparte la ubicación del
    navegador una sola vez y reenvía el formulario que lo envuelve con
    `lat`/`lng` en la URL. No guarda nada en el servidor ni pide permiso
    de forma automática — solo al hacer clic aquí.
--}}
<div
    x-data="clienteNearMeToggle()"
    x-init="init()"
    @class([
        'inline-flex items-center gap-2',
        'flex-wrap' => ! $compact,
        'shrink-0' => $compact,
    ])
>
    <input x-ref="lat" type="hidden" name="lat" value="{{ $near['lat'] ?? '' }}">
    <input x-ref="lng" type="hidden" name="lng" value="{{ $near['lng'] ?? '' }}">

    <flux:button
        type="button"
        :size="$compact ? 'xs' : 'sm'"
        :variant="$near ? 'primary' : 'ghost'"
        icon="map-pin"
        x-on:click="toggle()"
        x-bind:disabled="loading"
        @class([
            'whitespace-nowrap rounded-xl',
            'h-12 px-4' => $compact,
        ])
    >
        <span x-show="!loading">{{ $near ? __('Cerca de mí ✓') : __('Cerca de mí') }}</span>
        <span x-show="loading" x-cloak>{{ __('Ubicándote...') }}</span>
    </flux:button>

    <flux:text
        x-show="error"
        x-cloak
        @class([
            'text-red-600 dark:text-red-400',
            'text-xs' => $compact,
            'text-sm' => ! $compact,
        ])
    >
        {{ __('No pudimos acceder a tu ubicación.') }}
    </flux:text>
</div>

@once
    @push('scripts')
        <script>
            window.clienteNearMeToggle = function () {
                return {
                    loading: false,
                    error: false,
                    init() {
                        this.error = false;
                    },
                    toggle() {
                        const isActive = this.$refs.lat.value !== '' && this.$refs.lng.value !== '';

                        if (isActive) {
                            this.$refs.lat.value = '';
                            this.$refs.lng.value = '';
                            this.$el.closest('form').submit();
                            return;
                        }

                        if (! window.isSecureContext || ! ('geolocation' in navigator)) {
                            this.error = true;
                            return;
                        }

                        this.loading = true;
                        this.error = false;

                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                this.loading = false;
                                this.$refs.lat.value = position.coords.latitude;
                                this.$refs.lng.value = position.coords.longitude;
                                this.$el.closest('form').submit();
                            },
                            () => {
                                this.loading = false;
                                this.error = true;
                            },
                            { enableHighAccuracy: false, timeout: 8000, maximumAge: 300000 },
                        );
                    },
                };
            };
        </script>
    @endpush
@endonce
