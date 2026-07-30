<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Trust\Models\Recommendation;
use App\Models\User;

class ModerateRecommendation
{
    public function handle(Recommendation $recommendation, User $actor, string $status, ?string $response = null): Recommendation
    {
        $recommendation->forceFill([
            'status' => $status,
            'business_response' => $response ?? $recommendation->business_response,
            'moderated_by' => $actor->id,
            'moderated_at' => now(),
            'published_at' => $status === Recommendation::PUBLICADA ? ($recommendation->published_at ?? now()) : null,
        ])->save();

        app(RecordAuditLog::class)->handle($actor, 'recommendation.moderated', $recommendation, [
            'business_id' => $recommendation->business_id,
            'status' => $status,
        ]);

        return $recommendation->fresh();
    }
}
