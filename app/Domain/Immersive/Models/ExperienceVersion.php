<?php

namespace App\Domain\Immersive\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * IMM-014 del TODO inmersivo: historial de publicación de una experiencia.
 * Cada fila es una foto fija de la configuración en el momento de publicar
 * — permite ver qué cambió y, en una fase posterior, revertir sin rehacer
 * la edición a mano.
 *
 * @property array<string, mixed> $config_snapshot
 */
class ExperienceVersion extends Model
{
    protected $fillable = [
        'immersive_experience_id',
        'version',
        'config_snapshot',
        'checksum',
        'status',
        'author_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'config_snapshot' => 'array',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ImmersiveExperience, $this>
     */
    public function experience(): BelongsTo
    {
        return $this->belongsTo(ImmersiveExperience::class, 'immersive_experience_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
