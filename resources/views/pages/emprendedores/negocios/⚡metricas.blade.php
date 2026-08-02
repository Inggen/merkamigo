<?php

use App\Domain\Analytics\Actions\CalculateConversionFunnel;
use App\Domain\Analytics\Actions\CalculateNeedFunnelMetrics;
use App\Domain\Analytics\Actions\CalculateProductPerformance;
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

    /**
     * 2.3 del TODO: tiempo hasta primera propuesta y hasta conexión,
     * medidos sobre las propias propuestas de este negocio a solicitudes de
     * "Pídelo en Merkamigo".
     */
    #[Computed]
    public function needFunnel(): array
    {
        return app(CalculateNeedFunnelMetrics::class)->handle($this->business);
    }

    /**
     * 4.5 del TODO: embudo visita → clic a WhatsApp → pedido completado.
     */
    #[Computed]
    public function conversionFunnel(): array
    {
        return app(CalculateConversionFunnel::class)->handle($this->business);
    }

    /**
     * 4.5 del TODO: desglose de vistas y clics por producto.
     */
    #[Computed]
    public function productPerformance(): array
    {
        return app(CalculateProductPerformance::class)->handle($this->business);
    }

    public function formatHours(?float $hours): string
    {
        if ($hours === null) {
            return __('Sin datos suficientes');
        }

        if ($hours < 1) {
            return __('menos de 1 hora');
        }

        if ($hours < 48) {
            return trans_choice(':count hora|:count horas', round($hours), ['count' => round($hours)]);
        }

        $days = round($hours / 24);

        return trans_choice(':count día|:count días', $days, ['count' => $days]);
    }
}; ?>

@php
    $metrics = $this->metrics;
    $needFunnel = $this->needFunnel;
    $conversionFunnel = $this->conversionFunnel;
    $productPerformance = $this->productPerformance;
    $maxViews = max(1, ...array_column($metrics['views_by_day'], 'count'));
    $maxWhatsapp = max(1, ...array_column($metrics['whatsapp_clicks_by_day'], 'count'));

    $viewsDelta = $metrics['total_views'] - $metrics['previous_total_views'];
    $whatsappDelta = $metrics['total_whatsapp_clicks'] - $metrics['previous_total_whatsapp_clicks'];
@endphp

<section class="mx-auto w-full max-w-3xl space-y-8">
    <div class="flex items-center gap-1.5">
        <flux:heading size="xl">{{ __('Métricas') }}</flux:heading>
        <flux:tooltip :content="__('«Visitas» cuenta cuando alguien abre tu vitrina o un producto. «Contactos» cuenta cuando tocan el botón de WhatsApp — no leemos ni guardamos la conversación.')">
            <flux:icon.question-mark-circle class="size-4 shrink-0 text-zinc-400" variant="outline" />
        </flux:tooltip>
    </div>

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

    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:subheading class="mb-4">{{ __('Tus tiempos en "Pídelo en Merkamigo"') }}</flux:subheading>

        @if ($needFunnel['has_enough_data'])
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sueles responder en') }}</flux:text>
                    <div class="text-lg font-medium">{{ $this->formatHours($needFunnel['median_hours_to_first_offer']) }}</div>
                </div>
                <div>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tus propuestas suelen generar contacto en') }}</flux:text>
                    <div class="text-lg font-medium">{{ $this->formatHours($needFunnel['median_hours_to_connection']) }}</div>
                </div>
            </div>
        @else
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Aún no hay suficientes datos. Responde solicitudes en "Pídelo en Merkamigo" para ver tus tiempos aquí.') }}
            </flux:text>
        @endif
    </div>

    <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:subheading class="mb-4">{{ __('Tu embudo de conversión esta semana') }}</flux:subheading>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Visitas') }}</flux:text>
                <div class="text-2xl font-semibold">{{ $conversionFunnel['visits'] }}</div>
            </div>
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Contactos por WhatsApp') }}
                    @if ($conversionFunnel['visit_to_click_rate'] !== null)
                        <span class="text-zinc-400">({{ $conversionFunnel['visit_to_click_rate'] }}%)</span>
                    @endif
                </flux:text>
                <div class="text-2xl font-semibold">{{ $conversionFunnel['whatsapp_clicks'] }}</div>
            </div>
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Pedidos completados') }}
                    @if ($conversionFunnel['click_to_order_rate'] !== null)
                        <span class="text-zinc-400">({{ $conversionFunnel['click_to_order_rate'] }}%)</span>
                    @endif
                </flux:text>
                <div class="text-2xl font-semibold">{{ $conversionFunnel['completed_orders'] }}</div>
            </div>
        </div>
    </div>

    @if (! empty($productPerformance))
        <div class="rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:subheading class="mb-4">{{ __('Tus productos más vistos esta semana') }}</flux:subheading>

            <div class="space-y-2">
                @foreach ($productPerformance as $row)
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <flux:text class="truncate font-medium">{{ $row['product']->name }}</flux:text>
                        <div class="flex shrink-0 gap-4 text-zinc-500 dark:text-zinc-400">
                            <span>{{ $row['views'] }} {{ __('vistas') }}</span>
                            <span>{{ $row['whatsapp_clicks'] }} {{ __('contactos') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="text-center">
        <flux:button variant="ghost" size="sm" icon="arrow-down-tray" :href="route('emprendedores.negocios.metricas.exportar', $this->business)">
            {{ __('Exportar mis datos (CSV)') }}
        </flux:button>
    </div>
</section>
