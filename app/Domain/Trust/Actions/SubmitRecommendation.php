<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Trust\Models\OrderConfirmation;
use App\Domain\Trust\Models\Recommendation;
use App\Models\User;
use App\Support\Validation\Rules\NoLinks;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Un comprador deja una recomendación (3.3 del TODO), solo tras una
 * interacción elegible: el pedido debe estar `completado` y pertenecerle
 * a quien recomienda. Un pedido admite como máximo una recomendación
 * (`recommendations.order_confirmation_id` es único) — es el mecanismo de
 * "detectar patrones básicos de abuso" que pide el TODO, sin necesitar
 * heurísticas más sofisticadas para el volumen del piloto.
 */
class SubmitRecommendation
{
    /**
     * @param  array<int, string>  $tags
     */
    public function handle(OrderConfirmation $order, User $actor, string $body, int $rating, array $tags = []): Recommendation
    {
        if ($order->status !== OrderConfirmation::COMPLETADO) {
            throw new InvalidArgumentException('Solo puedes recomendar un pedido ya completado.');
        }

        if ($order->customer_user_id !== $actor->id) {
            throw new InvalidArgumentException('Solo el comprador de este pedido puede recomendarlo.');
        }

        if (Recommendation::where('order_confirmation_id', $order->id)->exists()) {
            throw new InvalidArgumentException('Ya enviaste una recomendación para este pedido.');
        }

        $validated = Validator::make(
            ['body' => $body, 'rating' => $rating, 'tags' => $tags],
            [
                'body' => ['required', 'string', 'max:500', new NoLinks],
                'rating' => ['required', 'integer', 'between:'.Recommendation::MIN_RATING.','.Recommendation::MAX_RATING],
                'tags' => ['array'],
                'tags.*' => ['string', 'in:'.implode(',', Recommendation::SUGGESTED_TAGS)],
            ],
        )->validate();

        return Recommendation::create([
            'business_id' => $order->business_id,
            'order_confirmation_id' => $order->id,
            'author_user_id' => $actor->id,
            'status' => Recommendation::PENDIENTE,
            'body' => $validated['body'],
            'rating' => $validated['rating'],
            'tags' => $validated['tags'],
        ]);
    }
}
