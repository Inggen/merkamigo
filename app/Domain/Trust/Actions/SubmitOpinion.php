<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Trust\Models\Recommendation;
use App\Models\User;
use App\Support\Validation\Rules\NoLinks;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Pedido del usuario: a diferencia de `SubmitRecommendation` (que exige un
 * pedido ya completado), una "opinión" sobre el negocio la puede dejar
 * cualquier usuario registrado sin haber comprado nada — el único
 * requisito es estar autenticado. Se guarda como una `Recommendation` más
 * (mismo modelo, misma cola de moderación en Filament) pero con
 * `order_confirmation_id` nulo, para no duplicar schema ni pipeline de
 * moderación. La columna ya era nullable y su índice único no choca con
 * varios nulos (NULL nunca es igual a otro NULL para UNIQUE en SQL).
 */
class SubmitOpinion
{
    /**
     * @param  array<int, string>  $tags
     */
    public function handle(Business $business, User $actor, string $body, int $rating, array $tags = []): Recommendation
    {
        $alreadySubmitted = Recommendation::query()
            ->where('business_id', $business->id)
            ->where('author_user_id', $actor->id)
            ->whereNull('order_confirmation_id')
            ->whereIn('status', [Recommendation::PENDIENTE, Recommendation::PUBLICADA])
            ->exists();

        if ($alreadySubmitted) {
            throw new InvalidArgumentException('Ya enviaste una opinión para este negocio.');
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
            'business_id' => $business->id,
            'order_confirmation_id' => null,
            'author_user_id' => $actor->id,
            'status' => Recommendation::PENDIENTE,
            'body' => $validated['body'],
            'rating' => $validated['rating'],
            'tags' => $validated['tags'],
        ]);
    }
}
