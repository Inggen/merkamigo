<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Models\Order;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Storefronts\Models\ProductVariant;
use App\Models\User;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Crea el pedido y calcula la firma de integridad en el servidor, igual
 * que `CreatePaymentCheckout` — pero firmado con la llave del NEGOCIO
 * (`BusinessWompiClient`), no la de Merkamigo: el pago va directo a la
 * cuenta del negocio.
 */
class CreateOrderCheckout
{
    public function handle(Product $product, int $quantity, User $buyer, ?ContentPromotion $promotion = null, ?ProductVariant $variant = null): Order
    {
        $business = $product->business;

        if (! $business->hasWompiConnected()) {
            throw new InvalidArgumentException('Este negocio todavía no activó pagos en línea.');
        }

        if ($product->price_type === 'consultar' || $product->price_type === 'sin_precio' || blank($product->price)) {
            throw new InvalidArgumentException('Este producto no tiene un precio fijo publicable para pagar en línea.');
        }

        if ($variant && $variant->product_id !== $product->id) {
            throw new InvalidArgumentException('La variante seleccionada no pertenece a este producto.');
        }

        $unitPrice = $variant?->price
            ?? ($product->hasActivePromo() && filled($product->promo_price) ? $product->promo_price : $product->price);
        $unitPriceCents = (int) round((float) $unitPrice * 100);
        $amountCents = $unitPriceCents * $quantity;
        $commissionCents = (int) round($amountCents * (float) config('services.marketplace.commission_rate'));

        return DB::transaction(function () use ($business, $product, $promotion, $buyer, $quantity, $unitPriceCents, $amountCents, $commissionCents, $variant): Order {
            $order = Order::create([
                'business_id' => $business->id,
                'product_id' => $product->id,
                'content_promotion_id' => $promotion?->id,
                'buyer_user_id' => $buyer->id,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'amount_cents' => $amountCents,
                'currency' => 'COP',
                'commission_cents' => $commissionCents,
                'reference' => 'MKA-ORD-'.$business->id.'-'.Str::upper(Str::random(12)),
                'status' => Order::PENDIENTE,
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'amount_cents' => $amountCents,
            ]);

            return $order;
        });
    }

    public function integritySignature(Order $order): string
    {
        $client = new BusinessWompiClient($order->business->wompiCredential);

        return $client->integritySignature($order->reference, $order->amount_cents, $order->currency);
    }
}
