<?php

namespace App\Domain\Social\Jobs;

use App\Domain\Social\Models\Post;
use App\Domain\Social\Notifications\NewPostPublished;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class NotifyUsersOfNewPost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private readonly int $postId) {}

    public function handle(): void
    {
        $post = Post::find($this->postId);

        if (! $post || ! $post->isPublished()) {
            return;
        }

        User::query()
            ->select('id')
            ->chunkById(500, function ($users) use ($post): void {
                Notification::send($users, new NewPostPublished($post));
            });
    }
}
