<?php

namespace App\Domain\Discovery\Concerns;

use App\Domain\Discovery\Models\Favorite;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Compartido por Business y Product (1.1.1/1.3 del TODO: guardar o quitar
 * de favoritos negocios y productos) para no duplicar la relación
 * polimórfica ni la comprobación de estado.
 */
trait Favoritable
{
    /**
     * @return MorphMany<Favorite, $this>
     */
    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favorites()->where('user_id', $user->id)->exists();
    }
}
