<?php

namespace App\Domain\Billing\Actions;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Billing\Models\Payment;
use App\Domain\Moderation\Actions\SubmitSupportTicket;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Aplica el efecto de un producto de ingreso complementario ya pagado
 * (4.3 del TODO). "Destacado" es 100% automático (reutiliza
 * `businesses.featured_until`, la misma columna que hoy mueve un
 * moderador a mano); "vitrina asistida" y "kit arranca bonito" tienen una
 * parte offline, así que se registran como solicitud de soporte para que
 * el equipo haga el seguimiento manual; un `entitlement` (ej. el chatbot
 * con IA) también es 100% automático, vía `BusinessEntitlement`.
 */
class ApplyBillingProductPurchase
{
    public function handle(Payment $payment): void
    {
        $product = $payment->billingProduct;

        if (! $product) {
            return;
        }

        match ($product->kind) {
            BillingProduct::DESTACADO => $this->applyFeatured($payment, $product),
            BillingProduct::VITRINA_ASISTIDA, BillingProduct::KIT_ARRANCA_BONITO => $this->requestManualFulfillment($payment, $product),
            BillingProduct::ENTITLEMENT => $this->applyEntitlement($payment, $product),
            default => null,
        };
    }

    private function applyFeatured(Payment $payment, BillingProduct $product): void
    {
        $business = $payment->business;
        $days = (int) ($product->payload['days'] ?? 7);

        $base = $business->isFeatured() ? $business->featured_until : now();
        $business->update(['featured_until' => $base->addDays($days)]);

        app(RecordAuditLog::class)->handle(null, 'business.featured_purchased', $business, [
            'payment_id' => $payment->id,
            'days' => $days,
        ]);
    }

    private function applyEntitlement(Payment $payment, BillingProduct $product): void
    {
        $business = $payment->business;
        $key = $product->payload['entitlement_key'] ?? null;

        if (! $key) {
            return;
        }

        $expiresInDays = $product->payload['expires_in_days'] ?? null;

        $entitlement = BusinessEntitlement::firstOrNew([
            'business_id' => $business->id,
            'key' => $key,
        ]);

        $entitlement->source_billing_product_id = $product->id;
        $entitlement->expires_at = $expiresInDays
            ? ($entitlement->exists && $entitlement->isActive() ? $entitlement->expires_at : now())->addDays($expiresInDays)
            : null;
        $entitlement->save();

        app(RecordAuditLog::class)->handle(null, 'business.entitlement_purchased', $business, [
            'payment_id' => $payment->id,
            'key' => $key,
        ]);
    }

    private function requestManualFulfillment(Payment $payment, BillingProduct $product): void
    {
        $business = $payment->business;
        $owner = User::find($business->organization->owner_user_id);

        app(SubmitSupportTicket::class)->handle(
            "Fulfillment: {$product->name}",
            "El negocio «{$business->name}» pagó «{$product->name}» (pago #{$payment->id}). Coordinar la parte manual de este servicio.",
            $owner,
            null,
        );
    }
}
