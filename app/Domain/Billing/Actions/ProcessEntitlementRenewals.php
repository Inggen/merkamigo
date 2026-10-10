<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Payment;
use App\Domain\Businesses\Models\Business;
use Illuminate\Support\Facades\Log;

/**
 * Corre a diario (PR4 de TODO_VENTAS_RENTABILIDAD.md, decisión #4):
 * cobra la renovación mensual de cada `BusinessEntitlement` recurrente
 * ya vencido o por vencer. Solo entra aquí un entitlement con
 * `expires_at` NO nulo — uno con `expires_at = null` es una compra de
 * por vida (ej. las que ya existían antes de esta decisión, dejadas
 * intactas a propósito: migrar a los compradores existentes requiere
 * primero el aviso previo que el usuario pidió, todavía pendiente de
 * definir con Comercial/Legal — ver docs/auditoria-ventas-rentabilidad.md
 * §8 punto 4).
 *
 * A diferencia de `ProcessSubscriptionRenewals` (planes), esta versión
 * NO tiene periodo de gracia: si el cobro falla, el entitlement
 * simplemente vence según su `expires_at` ya pasado y
 * `BusinessEntitlement::isActive()` empieza a devolver `false` — el
 * negocio pierde el acceso hasta que pague. Se intenta de nuevo cada
 * día mientras siga vencido (mismo criterio de reintento diario que ya
 * usa `ProcessSubscriptionRenewals::retryGracePeriodSubscriptions()`
 * para planes en gracia), así que no hace falta un estado de gracia
 * aparte para esto todavía.
 */
class ProcessEntitlementRenewals
{
    public function handle(): int
    {
        $count = 0;

        BusinessEntitlement::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->whereNotNull('source_billing_product_id')
            ->with(['business', 'sourceBillingProduct'])
            ->each(function (BusinessEntitlement $entitlement) use (&$count) {
                $business = $entitlement->business;
                $billingProduct = $entitlement->sourceBillingProduct;

                if (! $business || ! $billingProduct) {
                    return;
                }

                if ($this->alreadyCoveredByPlan($business, $entitlement)) {
                    // El negocio subió a un plan que ya incluye esto —
                    // no tiene sentido seguirle cobrando el add-on por
                    // separado (decisión #5 del usuario, misma razón
                    // por la que tampoco se le deja comprarlo de
                    // nuevo). El acceso sigue vivo por el plan aunque
                    // este entitlement puntual quede vencido.
                    return;
                }

                if (! $business->hasAutoRenewCard()) {
                    Log::warning("[Billing] Entitlement {$entitlement->id} (negocio {$business->id}, clave {$entitlement->key}) vencido sin tarjeta guardada — no se pudo renovar.");

                    return;
                }

                $payment = app(ChargeEntitlementRenewal::class)->handle($business, $billingProduct);

                if ($payment->status === Payment::APROBADO) {
                    $count++;
                }
            });

        return $count;
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
