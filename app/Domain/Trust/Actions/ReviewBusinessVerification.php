<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReviewBusinessVerification
{
    public function handle(
        BusinessVerification $verification,
        User $actor,
        string $status,
        ?string $reviewNote = null,
        ?Carbon $expiresAt = null,
        ?string $level = null,
    ): BusinessVerification {
        $verification->forceFill([
            'status' => $status,
            'review_note' => $reviewNote,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
            'expires_at' => $expiresAt,
            'level' => $level ?? $verification->level,
        ])->save();

        app(RecordAuditLog::class)->handle($actor, 'business.verification_reviewed', $verification, [
            'business_id' => $verification->business_id,
            'status' => $status,
        ]);

        return $verification->fresh();
    }
}
