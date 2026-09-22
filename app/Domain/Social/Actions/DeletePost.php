<?php

namespace App\Domain\Social\Actions;

use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Social\Models\Post;
use App\Models\User;

class DeletePost
{
    public function handle(Post $post, User $actor): void
    {
        $post->delete();

        app(RecordAuditLog::class)->handle($actor, 'post.deleted', $post, [
            'business_id' => $post->business_id,
        ]);
    }
}
