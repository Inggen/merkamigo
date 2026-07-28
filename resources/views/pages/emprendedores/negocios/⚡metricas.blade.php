<?php

use App\Domain\Analytics\Actions\CalculateReadableMetrics;
use App\Domain\Businesses\Models\Business;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Métricas comprensibles (1.8 del TODO): resumen semanal en lenguaje
 * humano, tarjetas de totales con comparación contra la semana anterior y
 * un gráfico simple de visitas/clics a WhatsApp por día.
 */
new #[Title('Métricas')] class extends Component {
    #[Locked]
    public int $businessId;

    /**
     * El middleware `business.team` solo corre en la carga inicial de la
     * página: las peticiones AJAX de Livewire van al endpoint genérico
     * `/livewire/update`, que no pasa por esa ruta ni por ese middleware.
     * `boot()` sí se ejecuta en cada petición (inicial y subsecuentes), así
     * que es el único lugar donde fijar el team de forma confiable en todo
     * el ciclo de vida del componente — sin esto, cualquier acción después
     * del primer render pierde el contexto de equipo y falla con 403.
     */
    public function boot(): void
    {
        if (isset($this->businessId)) {
            setPermissionsTeamId($this->businessId);
            Auth::user()?->unsetRelation('roles');
        }
    }

    public function mount(Business $business): void
    {
        setPermissionsTeamId($business->id);
        Auth::user()->unsetRelation('roles');

        $this->authorize('view', $business);

        $this->businessId = $business->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function metrics(): array
    {
        return app(CalculateReadableMetrics::class)->handle($this->business);
    }
}; ?>

@php
    $metrics = $this->metrics;
    $maxViews = max(1, ...array_column($metrics['views_by_day'], 'count'));
    $maxWhatsapp = max(1, ...array_column($metrics['whatsapp_clicks_by_day'], 'count'));

    $viewsDelta = $metrics['total_views'] - $metrics['previous_total_views'];
    $whatsappDelta = $metrics['total_whatsapp_clicks'] - $metrics['previous_total_whatsapp_clicks'];
@endphp

<section class="mx-auto w-full max-w-3xl space-y-8">
    <flux:heading size="xl">{{ __('Métricas') }}</flux:heading>

    <div class="rounded-2xl border border-brand-200 bg-brand-50 p-6 dark:border-brand-900 dark:bg-brand-950">
        <flux:text class="text-lg font-medium">{{ $metrics['summary'] }}</flux:text>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-1 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:subheading>{{ __('Visitas esta semana') }}</flux:subheading>
            <div class="text-3xl font-semibold">{{ $metrics['total_views'] }}</div>
            @if ($metrics['previous_total_views'] > 0)
                <flux:text class="text-sm text-zinc-500">
                    {{ $viewsDelta >= 0 ? '+' : '' }}{{ $viewsDelta }} {{ __('vs. semana pasada') }}
                </flux:text>
            @endif
            <flux:text class="text-xs text-zinc-400">
                {{ __('Personas que abrieron tu vitrina o alguno de tus productos.') }}
            </flux:text>
        </div>

        <div class="space-y-1 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:subheading>{{ __('Contactos por WhatsApp') }}</flux:subheading>
            <div class="text-3xl font-semibold">{{ $metrics['total_whatsapp_clicks'] }}</div>
            @if ($metrics['previous_total_whatsapp_clicks'] > 0)
                <flux:text class="text-sm text-zinc-500">
                    {{ $whatsappDelta >= 0 ? '+' : '' }}{{ $whatsappDelta }} {{ __('vs. semana pasada') }}
                </flux:text>
            @endif
            <flux:text class="text-xs text-zinc-400">
                {{ __('Veces que alguien tocó el botón de WhatsApp en tu vitrina o tus productos.') }}
            </flux:text>
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:subheading class="mb-4">{{ __('Visitas por día') }}</flux:subheading>
        <div class="flex h-32 items-end gap-2">
            @foreach ($metrics['views_by_day'] as $day)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <div class="w-full rounded-t bg-brand-500" style="height: {{ max(4, ($day['count'] / $maxViews) * 100) }}%"></div>
                    <flux:text class="text-xs text-zinc-400">{{ $day['label'] }}</flux:text>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:subheading class="mb-4">{{ __('Contactos por WhatsApp por día') }}</flux:subheading>
        <div class="flex h-32 items-end gap-2">
            @foreach ($metrics['whatsapp_clicks_by_day'] as $day)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <div class="w-full rounded-t bg-emerald-500" style="height: {{ max(4, ($day['count'] / $maxWhatsapp) * 100) }}%"></div>
                    <flux:text class="text-xs text-zinc-400">{{ $day['label'] }}</flux:text>
                </div>
            @endforeach
        </div>
    </div>
</section>
