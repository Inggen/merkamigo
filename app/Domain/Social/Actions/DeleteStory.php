<?php

namespace App\Domain\Social\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Models\Story;
use App\Models\User;

class DeleteStory
{
    public function handle(Story $story, User $actor): void
    {
        $story->delete();

        app(RecordAuditLog::class)->handle($actor, 'story.deleted', $story, [
            'business_id' => $story->business_id,
        ]);
    }
}
