<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Marketplace\Models\Order;
use App\Domain\Social\Models\LiveStream;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreateLiveOrderCheckout
{
    /**
     * @param  array<int, array{product_id: int, variant_id?: int|null, quantity: int}>  $cart
     */
    public function handle(LiveStream $liveStream, array $cart, User $buyer): Order
    {
        $liveStream->loadMissing(['business.wompiCredential', 'products.variants', 'activePromotion']);

        if (! $liveStream->business->hasWompiConnected()) {
            throw new InvalidArgumentException('Este negocio todavía no activó pagos en línea.');
        }

        if ($cart === []) {
            throw new InvalidArgumentException('Agrega al menos un producto al carrito.');
        }

        $items = collect($cart)->map(function (array $cartItem) use ($liveStream): array {
            $product = $liveStream->products->firstWhere('id', (int) $cartItem['product_id']);

            if (! $product || $product->isSoldOut() || blank($product->price)) {
                throw new InvalidArgumentException('Uno de los productos ya no está disponible.');
            }

            $variant = filled($cartItem['variant_id'] ?? null)
                ? $product->variants->firstWhere('id', (int) $cartItem['variant_id'])
                : null;

            if (filled($cartItem['variant_id'] ?? null) && ! $variant) {
                throw new InvalidArgumentException('Una de las variantes seleccionadas ya no está disponible.');
            }

            $quantity = max(1, min(99, (int) $cartItem['quantity']));
            $price = $variant?->price
                ?? ($product->hasActivePromo() && filled($product->promo_price) ? $product->promo_price : $product->price);
            $unitPriceCents = (int) round((float) $price * 100);

            return [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'amount_cents' => $unitPriceCents * $quantity,
            ];
        })->values();

        $amountCents = (int) $items->sum('amount_cents');
        $commissionCents = (int) round($amountCents * (float) config('services.marketplace.commission_rate'));
        $first = $items->first();

        return DB::transaction(function () use ($liveStream, $buyer, $items, $amountCents, $commissionCents, $first): Order {
            $order = Order::create([
                'business_id' => $liveStream->business_id,
                'product_id' => $first['product']->id,
                'content_promotion_id' => $liveStream->activePromotion?->id,
                'live_stream_id' => $liveStream->id,
                'buyer_user_id' => $buyer->id,
                'quantity' => (int) $items->sum('quantity'),
                'unit_price_cents' => $first['unit_price_cents'],
                'amount_cents' => $amountCents,
                'currency' => 'COP',
                'commission_cents' => $commissionCents,
                'reference' => 'MKA-LIVE-'.$liveStream->id.'-'.Str::upper(Str::random(12)),
                'status' => Order::PENDIENTE,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_variant_id' => $item['variant']?->id,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $item['unit_price_cents'],
                    'amount_cents' => $item['amount_cents'],
                ]);
            }

            return $order->load('items.product', 'items.variant');
        });
    }
}
