<?php

namespace App\Livewire;

use App\Domain\Social\Actions\CreatePostComment;
use App\Domain\Social\Models\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Comentarios de un post en el feed (2.2 del TODO social, Sprint 2).
 * Colapsado por defecto para no sobrecargar el feed (23 del TODO: "evitar
 * sobrecargar pantallas").
 */
class PostComments extends Component
{
    public int $postId;

    public bool $expanded = false;

    public string $body = '';

    public function mount(Post $post): void
    {
        $this->postId = $post->id;
    }

    public function toggleExpanded(): void
    {
        $this->expanded = ! $this->expanded;
    }

    public function submit(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $post = Post::findOrFail($this->postId);

        try {
            app(CreatePostComment::class)->handle($post, ['body' => $this->body], Auth::user());
        } catch (ValidationException $e) {
            $this->addError('body', $e->validator->errors()->first());

            return;
        }

        $this->reset('body');
        $this->expanded = true;
    }

    public function render(): View
    {
        $post = Post::with(['visibleComments.user'])->findOrFail($this->postId);

        return view('livewire.post-comments', [
            'comments' => $post->visibleComments,
        ]);
    }
}
