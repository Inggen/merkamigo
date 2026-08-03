<?php

namespace App\Domain\Needs\Actions;

use App\Domain\Needs\Exceptions\IncompleteNeedException;
use App\Domain\Needs\Models\Need;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Publica una necesidad (2.1 del TODO): valida los datos mínimos y
 * transiciona a "publicada" con su fecha de expiración por defecto.
 */
class PublishNeed
{
    public function handle(Need $need, User $actor): Need
    {
        $missing = $this->missingFields($need);

        if ($missing !== []) {
            throw new IncompleteNeedException($missing);
        }

        $need->update([
            'status' => Need::PUBLICADA,
            'published_at' => $need->published_at ?? now(),
            'expires_at' => $need->expires_at ?? now()->addDays(Need::DEFAULT_LIFETIME_DAYS),
        ]);

        app(RecordAuditLog::class)->handle($actor, 'need.published', $need);

        return $need->fresh();
    }

    /**
     * @return array<int, string>
     */
    public function missingFieldsFor(Need $need): array
    {
        return $this->missingFields($need);
    }

    /**
     * @return array<int, string>
     */
    private function missingFields(Need $need): array
    {
        $missing = [];

        if (blank($need->title)) {
            $missing[] = 'Qué necesitas';
        }

        if (blank($need->description)) {
            $missing[] = 'Descripción';
        }

        if (blank($need->municipality_id)) {
            $missing[] = 'Municipio';
        }

        if (blank($need->category_id)) {
            $missing[] = 'Categoría';
        }

        return $missing;
    }
}
