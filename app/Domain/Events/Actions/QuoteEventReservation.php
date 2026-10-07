<?php

namespace App\Domain\Events\Actions;

use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventBusinessEquipment;
use App\Domain\Events\Models\EventDish;
use App\Domain\Events\Models\EventSetting;

/**
 * Cálculo puro del valor de una reserva de evento
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 3). Única fuente de
 * verdad para el total: la usa tanto la vista previa en vivo del
 * cotizador como `CreateEventReservation` al confirmar — nunca se confía
 * en un monto calculado en el cliente.
 *
 * Fórmulas (Fase 3): `platos = Σ(cantidad × precio)`;
 * `lugar = duración × precio/hora`; `equipos = Σ(cantidad × cargo)`;
 * el total usa platos, lugar o ambos según la modalidad, más equipos.
 */
class QuoteEventReservation
{
    /**
     * @param  array<int, array{dish: EventDish, quantity: int}>  $dishSelections
     * @param  array<int, array{equipment: EventBusinessEquipment, quantity: int}>  $equipmentSelections
     * @return array{space_total_cents: int, dishes_total_cents: int, equipment_total_cents: int, total_cents: int, lines: array<int, array{label: string, amount_cents: int}>}
     */
    public function handle(EventSetting $settings, int $durationHours, array $dishSelections, array $equipmentSelections): array
    {
        $lines = [];

        $spaceTotalCents = 0;
        if (in_array($settings->pricing_mode, [EventSetting::PRICING_HORAS, EventSetting::PRICING_HIBRIDO], true)) {
            if (! $settings->hourly_rate_cents) {
                throw new EventActionException('Este negocio no tiene definida una tarifa por hora.');
            }

            $spaceTotalCents = $durationHours * $settings->hourly_rate_cents;
            $lines[] = ['label' => __('Alquiler del espacio (:hours h)', ['hours' => $durationHours]), 'amount_cents' => $spaceTotalCents];
        }

        $dishesTotalCents = 0;
        if (in_array($settings->pricing_mode, [EventSetting::PRICING_PLATOS, EventSetting::PRICING_HIBRIDO], true)) {
            foreach ($dishSelections as $selection) {
                $quantity = $selection['quantity'];

                if ($quantity <= 0) {
                    continue;
                }

                $amount = $quantity * $selection['dish']->price_cents;
                $dishesTotalCents += $amount;
                $lines[] = ['label' => $quantity.'× '.$selection['dish']->name, 'amount_cents' => $amount];
            }
        }

        $equipmentTotalCents = 0;
        foreach ($equipmentSelections as $selection) {
            $quantity = $selection['quantity'];

            if ($quantity <= 0) {
                continue;
            }

            $fee = $selection['equipment']->fee_cents ?? 0;
            $amount = $quantity * $fee;
            $equipmentTotalCents += $amount;

            if ($amount > 0) {
                $lines[] = ['label' => $selection['equipment']->displayName(), 'amount_cents' => $amount];
            }
        }

        return [
            'space_total_cents' => $spaceTotalCents,
            'dishes_total_cents' => $dishesTotalCents,
            'equipment_total_cents' => $equipmentTotalCents,
            'total_cents' => $spaceTotalCents + $dishesTotalCents + $equipmentTotalCents,
            'lines' => $lines,
        ];
    }
}
