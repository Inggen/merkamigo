<?php

namespace App\Domain\Discovery\Actions;

use App\Domain\Discovery\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Guarda o quita un negocio o producto de favoritos (1.1.1/1.3 del TODO).
 * Misma acción para ambos tipos gracias a la relación polimórfica de
 * `favorites` — no se duplica la regla entre negocio y producto.
 */
class ToggleFavorite
{
    /**
     * @return bool true si quedó como favorito, false si se quitó.
     */
    public function handle(User $user, Model $favoritable): bool
    {
        $existing = Favorite::query()
            ->where('user_id', $user->id)
            ->where('favoritable_type', $favoritable->getMorphClass())
            ->where('favoritable_id', $favoritable->getKey())
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        Favorite::create([
            'user_id' => $user->id,
            'favoritable_type' => $favoritable->getMorphClass(),
            'favoritable_id' => $favoritable->getKey(),
        ]);

        return true;
    }
}
