<?php

namespace App\Livewire;

use App\Domain\Social\Actions\TogglePostReaction;
use App\Domain\Social\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * "Me gusta" de un post en el feed (2.2 del TODO social, Sprint 2).
 */
class PostReactionButton extends Component
{
    public int $postId;

    public bool $reacted = false;

    public int $count = 0;

    public function mount(Post $post): void
    {
        $this->postId = $post->id;
        $this->reacted = $post->isReactedBy(Auth::user());
        $this->count = $post->reactions()->count();
    }

    public function toggle(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $post = Post::findOrFail($this->postId);

        $this->reacted = app(TogglePostReaction::class)->handle(Auth::user(), $post);
        $this->count = $post->reactions()->count();
    }

    public function render(): View
    {
        return view('livewire.post-reaction-button');
    }
}
