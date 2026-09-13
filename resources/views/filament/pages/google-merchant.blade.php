@php($stats = $this->stats())

<x-filament-panels::page>
    @unless ($stats['enabled'])
        <div class="rounded-lg border border-warning-300 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-800 dark:bg-warning-950 dark:text-warning-200">
            La integración con Google Merchant está <strong>desactivada</strong> (<code>GOOGLE_MERCHANT_ENABLED=false</code>). Ningún producto se envía a Google mientras esté así.
        </div>
    @endunless

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <x-filament::section>
            <div class="text-sm text-gray-500">Negocios habilitados</div>
            <div class="text-2xl font-semibold">{{ $stats['negocios_habilitados'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Productos elegibles</div>
            <div class="text-2xl font-semibold">{{ $stats['elegibles'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Publicados en Google</div>
            <div class="text-2xl font-semibold text-success-600">{{ $stats['publicados'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Requieren ajustes</div>
            <div class="text-2xl font-semibold text-warning-600">{{ $stats['requiere_ajustes'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Con error</div>
            <div class="text-2xl font-semibold text-danger-600">{{ $stats['error'] }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Última sincronización</div>
            <div class="text-base font-semibold">
                {{ $stats['ultima_sincronizacion'] ? \Illuminate\Support\Carbon::parse($stats['ultima_sincronizacion'])->diffForHumans() : 'Nunca' }}
            </div>
        </x-filament::section>
    </div>

    {{ $this->table }}
</x-filament-panels::page>
