<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Notifications\OrderConfirmedByBusiness;
use App\Models\User;

class ConfirmOrder
{
    public function confirmAsCustomer(OrderConfirmation $order, User $actor): OrderConfirmation
    {
        $order->forceFill([
            'customer_user_id' => $order->customer_user_id ?? $actor->id,
            'customer_confirmed_at' => $order->customer_confirmed_at ?? now(),
        ])->save();

        return $this->syncStatus($order, $actor, 'order.confirmed_by_customer');
    }

    public function confirmAsBusiness(OrderConfirmation $order, User $actor): OrderConfirmation
    {
        $order->forceFill([
            'business_user_id' => $order->business_user_id ?? $actor->id,
            'business_confirmed_at' => $order->business_confirmed_at ?? now(),
        ])->save();

        $order = $this->syncStatus($order, $actor, 'order.confirmed_by_business');

        if ($order->status === OrderConfirmation::CONFIRMADO && $order->customerUser) {
            $order->customerUser->notify(new OrderConfirmedByBusiness($order));
        }

        return $order;
    }

    public function complete(OrderConfirmation $order, User $actor): OrderConfirmation
    {
        if (! $order->canBeCompleted()) {
            return $order;
        }

        $order->forceFill([
            'status' => OrderConfirmation::COMPLETADO,
            'completed_at' => now(),
            'is_reputation_eligible' => true,
        ])->save();

        app(RecordAuditLog::class)->handle($actor, 'order.completed', $order, [
            'business_id' => $order->business_id,
        ]);

        return $order->fresh();
    }

    public function markDisputed(OrderConfirmation $order, User $actor, ?string $note = null): OrderConfirmation
    {
        $order->forceFill([
            'status' => OrderConfirmation::EN_DISPUTA,
            'dispute_note' => $note,
            'is_reputation_eligible' => false,
        ])->save();

        app(RecordAuditLog::class)->handle($actor, 'order.disputed', $order, [
            'business_id' => $order->business_id,
        ]);

        return $order->fresh();
    }

    /**
     * Cancela un pedido antes de completarse (2.3/3.2 del TODO). Cualquiera
     * de las dos partes puede cancelar mientras no esté `COMPLETADO` — una
     * vez completado, la constancia queda fija (usar `markDisputed()` si
     * algo salió mal después de completarse).
     */
    public function cancel(OrderConfirmation $order, User $actor, ?string $reason = null): OrderConfirmation
    {
        if ($order->status === OrderConfirmation::COMPLETADO) {
            return $order;
        }

        $order->forceFill([
            'status' => OrderConfirmation::CANCELADO,
            'dispute_note' => $reason,
            'is_reputation_eligible' => false,
        ])->save();

        app(RecordAuditLog::class)->handle($actor, 'order.cancelled', $order, [
            'business_id' => $order->business_id,
        ]);

        return $order->fresh();
    }

    private function syncStatus(OrderConfirmation $order, User $actor, string $action): OrderConfirmation
    {
        $shouldBeConfirmed = $order->customer_confirmed_at && $order->business_confirmed_at;

        if ($shouldBeConfirmed) {
            $order->status = OrderConfirmation::CONFIRMADO;
            $order->save();
        }

        app(RecordAuditLog::class)->handle($actor, $action, $order, [
            'business_id' => $order->business_id,
        ]);

        return $order->fresh();
    }
}
