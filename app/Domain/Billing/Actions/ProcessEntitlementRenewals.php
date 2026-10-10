<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Notifications\EntitlementRenewalDue;
use App\Domain\Businesses\Models\Business;

/**
 * Corre a diario (PR4 de TODO_VENTAS_RENTABILIDAD.md, pedido del
 * usuario: "deja el cobro mensual igual de robusto que la renovación de
 * planes") — mismo patrón de dos fases que
 * `ProcessSubscriptionRenewals`:
 *
 * - Entitlements `activa` ya vencidos (`expires_at` pasado): si el
 *   negocio tiene tarjeta guardada se intenta el cobro ahí mismo; si no
 *   hay tarjeta o el cobro es rechazado, entra a `en_gracia` con
 *   `GRACE_DAYS` para regularizar sin perder el acceso de inmediato.
 * - Entitlements ya `en_gracia` con tarjeta guardada: reintento diario
 *   mientras dure la gracia.
 *
 * Solo entra aquí un entitlement con `expires_at` NO nulo — uno con
 * `expires_at = null` es una compra de por vida (ej. las que ya
 * existían antes de esta decisión, dejadas intactas a propósito:
 * migrar a los compradores existentes requiere primero el aviso previo
 * que el usuario pidió, todavía pendiente de definir con Comercial/
 * Legal — ver docs/auditoria-ventas-rentabilidad.md §8 punto 4).
 *
 * A diferencia de los planes, agotada la gracia no hay un "plan Gratis"
 * al que degradar — el entitlement simplemente queda vencido
 * (`BusinessEntitlement::isActive()` empieza a devolver `false`) hasta
 * que una compra o renovación futura lo reactive.
 */
class ProcessEntitlementRenewals
{
    public const GRACE_DAYS = 3;

    public function handle(): int
    {
        $processed = 0;
        $justTransitioned = [];

        $processed += $this->processExpiredActiveEntitlements($justTransitioned);
        $processed += $this->retryGracePeriodEntitlements($justTransitioned);

        return $processed;
    }

    /**
     * @param  array<int, int>  $justTransitioned  IDs de entitlement recién movidos a `en_gracia` en esta misma
     *                                             corrida — se excluyen del reintento de abajo para no cobrar dos
     *                                             veces el mismo día.
     */
    private function processExpiredActiveEntitlements(array &$justTransitioned): int
    {
        $count = 0;

        BusinessEntitlement::query()
            ->where('status', BusinessEntitlement::ACTIVA)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotNull('source_billing_product_id')
            ->with(['business', 'sourceBillingProduct'])
            ->each(function (BusinessEntitlement $entitlement) use (&$count, &$justTransitioned) {
                $business = $entitlement->business;
                $billingProduct = $entitlement->sourceBillingProduct;

                if (! $business || ! $billingProduct || $this->alreadyCoveredByPlan($business, $entitlement)) {
                    return;
                }

                if ($business->hasAutoRenewCard() && $this->attemptCharge($business, $entitlement, $billingProduct)) {
                    $count++;

                    return;
                }

                $entitlement->update([
                    'status' => BusinessEntitlement::EN_GRACIA,
                    'grace_ends_at' => now()->addDays(self::GRACE_DAYS),
                ]);
                $justTransitioned[] = $entitlement->id;

                if (! $business->hasAutoRenewCard()) {
                    $business->members->each(fn ($member) => $member->notify(new EntitlementRenewalDue($entitlement)));
                }

                $count++;
            });

        return $count;
    }

    /**
     * @param  array<int, int>  $skipEntitlementIds
     */
    private function retryGracePeriodEntitlements(array $skipEntitlementIds): int
    {
        $count = 0;

        BusinessEntitlement::query()
            ->whereNotIn('id', $skipEntitlementIds)
            ->where('status', BusinessEntitlement::EN_GRACIA)
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '>=', now())
            ->whereNotNull('source_billing_product_id')
            ->with(['business', 'sourceBillingProduct'])
            ->each(function (BusinessEntitlement $entitlement) use (&$count) {
                $business = $entitlement->business;
                $billingProduct = $entitlement->sourceBillingProduct;

                if (! $business || ! $billingProduct || $this->alreadyCoveredByPlan($business, $entitlement)) {
                    return;
                }

                if (! $business->hasAutoRenewCard()) {
                    return;
                }

                if ($this->attemptCharge($business, $entitlement, $billingProduct)) {
                    $count++;
                }
            });

        return $count;
    }

    private function attemptCharge(Business $business, BusinessEntitlement $entitlement, BillingProduct $billingProduct): bool
    {
        $payment = app(ChargeEntitlementRenewal::class)->handle($business, $billingProduct);

        if ($payment->status !== Payment::APROBADO) {
            return false;
        }

        // `ApplyBillingProductPurchase::applyEntitlement()` ya extendió
        // `expires_at` como parte de aplicar el pago — acá solo se
        // cierra el ciclo de gracia (si venía de uno) y se garantiza que
        // la extensión parte de HOY, no del vencimiento viejo: igual
        // que `ApplyApprovedPayment::applyEffect()` hace para la
        // renovación de planes, una renovación (a tiempo o en gracia)
        // siempre cuenta desde el momento del cobro, nunca "recupera"
        // los días ya perdidos ni los resta.
        $expiresInDays = $billingProduct->payload['expires_in_days'] ?? null;

        $entitlement->update([
            'status' => BusinessEntitlement::ACTIVA,
            'grace_ends_at' => null,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : $entitlement->expires_at,
        ]);

        return true;
    }

    /**
     * Hoy solo existe un entitlement recurrente (el chatbot IA,
     * exclusivo también del plan Negocios) — cuando exista un segundo
     * caso con una regla de plan distinta, esto se vuelve un `match`
     * por `$entitlement->key`.
     */
    private function alreadyCoveredByPlan(Business $business, BusinessEntitlement $entitlement): bool
    {
        return $entitlement->key === BusinessEntitlement::AI_CHATBOT && $business->isOnTopPlan();
    }
}
