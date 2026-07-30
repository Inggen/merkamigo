<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Models\Need;
use App\Domain\Needs\Models\Offer;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Trust\Actions\ConfirmOrder;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cierre de una solicitud por su dueño (2.3 del TODO): "contacté",
 * "encontré lo que buscaba" o "no encontré". Cuando el comprador marca que
 * encontró lo que buscaba a través de una propuesta concreta, se prepara
 * el evento para el Pasaporte de confianza (Fase 3) creando una
 * `OrderConfirmation` enlazada a esa propuesta y confirmando el lado del
 * comprador — el lado del negocio queda pendiente, tal como cualquier otro
 * pedido confirmado.
 */
class CloseNeed
{
    private const OUTCOMES = [Need::OUTCOME_CONTACTE, Need::OUTCOME_ENCONTRE, Need::OUTCOME_NO_ENCONTRE];

    public function handle(Need $need, User $actor, string $outcome, ?Offer $selectedOffer = null): Need
    {
        if (! in_array($outcome, self::OUTCOMES, true)) {
            throw new InvalidArgumentException("Resultado de solicitud inválido: {$outcome}.");
        }

        if ($selectedOffer && $selectedOffer->need_id !== $need->id) {
            throw new InvalidArgumentException('La propuesta seleccionada no pertenece a esta solicitud.');
        }

        return DB::transaction(function () use ($need, $actor, $outcome, $selectedOffer) {
            $need->update([
                'status' => Need::CERRADA,
                'outcome' => $outcome,
                'selected_offer_id' => $selectedOffer !== null ? $selectedOffer->id : $need->selected_offer_id,
                'closed_at' => now(),
            ]);

            if ($selectedOffer) {
                $selectedOffer->update(['status' => Offer::ACEPTADA]);
            }

            if ($outcome === Need::OUTCOME_ENCONTRE && $selectedOffer) {
                $this->prepareTrustEvent($need, $selectedOffer, $actor);
            }

            app(RecordAuditLog::class)->handle($actor, 'need.closed', $need, ['outcome' => $outcome]);

            return $need->fresh();
        });
    }

    private function prepareTrustEvent(Need $need, Offer $selectedOffer, User $actor): void
    {
        $order = OrderConfirmation::firstOrCreate(
            ['source_type' => (new Offer)->getMorphClass(), 'source_id' => $selectedOffer->id],
            [
                'business_id' => $selectedOffer->business_id,
                'created_by' => $actor->id,
                'status' => OrderConfirmation::PENDIENTE,
                'summary' => "Necesidad: {$need->title}",
            ],
        );

        app(ConfirmOrder::class)->confirmAsCustomer($order, $actor);
    }
}
