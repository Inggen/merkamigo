<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Notifications\OrderPendingYourConfirmation;
use App\Models\User;
use InvalidArgumentException;

/**
 * Constancia de pedido "desde un contacto" fuera de "Pídelo en Merkamigo"
 * (3.2 del TODO). A diferencia de crear una `OrderConfirmation`
 * automáticamente en cada clic de WhatsApp —que sería fabricar
 * confirmaciones, justo lo que 3.2 prohíbe—, el negocio registra el correo
 * real del comprador: si esa cuenta no existe, se rechaza. El lado del
 * negocio se confirma de inmediato; el del comprador queda pendiente hasta
 * que esa persona lo confirme desde "Mis pedidos".
 */
class RequestDirectOrderConfirmation
{
    public function handle(Business $business, User $actor, string $customerEmail, string $summary): OrderConfirmation
    {
        $customer = User::where('email', $customerEmail)->first();

        if (! $customer) {
            throw new InvalidArgumentException('Ese correo no tiene una cuenta en Merkamigo todavía.');
        }

        if ($customer->id === $actor->id) {
            throw new InvalidArgumentException('No puedes registrar un pedido contigo mismo como comprador.');
        }

        $order = OrderConfirmation::create([
            'business_id' => $business->id,
            'created_by' => $actor->id,
            'customer_user_id' => $customer->id,
            'source_type' => $business->getMorphClass(),
            'source_id' => $business->id,
            'summary' => $summary,
        ]);

        $order = app(ConfirmOrder::class)->confirmAsBusiness($order, $actor);

        $customer->notify(new OrderPendingYourConfirmation($order));

        return $order;
    }
}
