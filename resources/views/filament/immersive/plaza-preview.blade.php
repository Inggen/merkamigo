@php
    $zoneColors = ['#2563eb', '#16a34a', '#d97706', '#9333ea', '#dc2626', '#0891b2'];
    $slotColors = ['disponible' => '#16a34a', 'ocupada' => '#2563eb', 'bloqueada' => '#6b7280', 'invalida' => '#dc2626'];
    $hasBounds = filled($plaza->navigable_bounds);
    $isEmpty = $plaza->zones->isEmpty() && $plaza->props->isEmpty();
@endphp

<div style="display: grid; gap: 12px;">
    @if (! $hasBounds)
        <p style="color: #6b7280; font-size: 0.875rem;">
            Esta plaza todavía no tiene límites navegables configurados — no hay suficiente información para dibujar la previsualización. Configúralos en el formulario de la plaza.
        </p>
    @else
        @if ($isEmpty)
            <p style="color: #92400e; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 8px 12px; font-size: 0.8rem;">
                Esta plaza todavía no tiene zonas, slots ni elementos — por eso el recuadro de abajo se ve vacío (no es un error). Sigue el paso a paso: sube el plano + su leyenda, mapea los colores en "Leyenda de colores", corre "Generar ubicaciones" desde esta plaza, o crea zonas/elementos a mano.
            </p>
        @endif
        <div style="position: relative; width: 100%; aspect-ratio: 4 / 3; background: #e5e7eb; border-radius: 8px; overflow: hidden;">
            @if ($plaza->reference_image_path)
                <img
                    src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($plaza->reference_image_path) }}"
                    alt="Plano de referencia"
                    style="position: absolute; inset: 0; width: 100%; height: 100%; object-fit: fill;"
                />
            @endif

            <svg viewBox="0 0 100 100" preserveAspectRatio="none" style="position: absolute; inset: 0; width: 100%; height: 100%;">
                @foreach ($plaza->zones as $zoneIndex => $zone)
                    @php
                        $color = $zoneColors[$zoneIndex % count($zoneColors)];
                        $points = collect($zone->polygon['points'] ?? [])
                            ->map(fn ($point) => $plaza->worldToImagePercent($point['x'], $point['z']))
                            ->filter()
                            ->map(fn ($p) => "{$p['xPercent']},{$p['yPercent']}")
                            ->implode(' ');
                    @endphp
                    @if ($points)
                        <polygon points="{{ $points }}" fill="{{ $color }}" fill-opacity="0.15" stroke="{{ $color }}" stroke-width="0.4" vector-effect="non-scaling-stroke" />
                    @endif
                @endforeach

                @foreach ($plaza->excluded_zones ?? [] as $excluded)
                    @php
                        $points = collect($excluded['points'] ?? [])
                            ->map(fn ($point) => $plaza->worldToImagePercent($point['x'], $point['z']))
                            ->filter()
                            ->map(fn ($p) => "{$p['xPercent']},{$p['yPercent']}")
                            ->implode(' ');
                    @endphp
                    @if ($points)
                        <polygon points="{{ $points }}" fill="#dc2626" fill-opacity="0.18" stroke="#dc2626" stroke-width="0.4" stroke-dasharray="1.2" vector-effect="non-scaling-stroke" />
                    @endif
                @endforeach

                @foreach ($plaza->zones as $zone)
                    @foreach ($zone->slots as $slot)
                        @php
                            $pos = $plaza->worldToImagePercent($slot->world_position['x'], $slot->world_position['z']);
                            $color = $slotColors[$slot->status] ?? '#6b7280';
                        @endphp
                        @if ($pos)
                            <circle cx="{{ $pos['xPercent'] }}" cy="{{ $pos['yPercent'] }}" r="1.3" fill="{{ $color }}" stroke="white" stroke-width="0.3" vector-effect="non-scaling-stroke">
                                <title>{{ $slot->code }} — {{ $slot->status }}</title>
                            </circle>
                        @endif
                    @endforeach
                @endforeach

                @foreach ($plaza->props as $prop)
                    @php
                        $pos = $plaza->worldToImagePercent($prop->world_position['x'], $prop->world_position['z']);
                    @endphp
                    @if ($pos)
                        <rect x="{{ $pos['xPercent'] - 1 }}" y="{{ $pos['yPercent'] - 1 }}" width="2" height="2" fill="#78350f" stroke="white" stroke-width="0.3" vector-effect="non-scaling-stroke">
                            <title>{{ $prop->template?->name }}</title>
                        </rect>
                    @endif
                @endforeach
            </svg>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.8rem; color: #374151;">
            @foreach ($plaza->zones as $zoneIndex => $zone)
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 2px; background: {{ $zoneColors[$zoneIndex % count($zoneColors)] }}; display: inline-block;"></span>
                    {{ $zone->name }} ({{ $zone->slots->count() }} slots)
                </span>
            @endforeach
            @if (filled($plaza->excluded_zones))
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 2px; background: #dc2626; display: inline-block;"></span>
                    Zona excluida
                </span>
            @endif
            @if ($plaza->props->isNotEmpty())
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <span style="width: 10px; height: 10px; border-radius: 2px; background: #78350f; display: inline-block;"></span>
                    Elemento de plaza (cuadro)
                </span>
            @endif
        </div>

        @if ($plaza->zones->flatMap->slots->isNotEmpty())
            <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.75rem; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px;">
                <span style="font-weight: 600; color: #374151;">Estado de los slots (círculo):</span>
                @foreach ($slotColors as $label => $color)
                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                        <span style="width: 9px; height: 9px; border-radius: 999px; background: {{ $color }}; display: inline-block;"></span>
                        {{ ucfirst($label) }}
                    </span>
                @endforeach
            </div>
        @endif
    @endif
</div>
