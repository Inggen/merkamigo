<div class="contents">
    <button
        type="button"
        wire:click="toggleExpanded"
        aria-expanded="{{ $expanded ? 'true' : 'false' }}"
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-sm font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-brand-600 dark:text-zinc-400 dark:hover:bg-zinc-800"
    >
        <flux:icon.chat-bubble-left class="size-4" variant="outline" />
        {{ $comments->count() > 0 ? trans_choice(':count comentario|:count comentarios', $comments->count(), ['count' => $comments->count()]) : __('Comentar') }}
    </button>

    @if ($expanded)
        <div class="order-last mt-3 w-full basis-full space-y-3">
            @foreach ($comments as $comment)
                <div class="flex items-start gap-2">
                    <flux:avatar size="xs" :name="$comment->user->name" :src="$comment->user->avatarUrl()" circle />
                    <div class="min-w-0 rounded-2xl bg-zinc-100 px-3 py-2 dark:bg-zinc-800">
                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $comment->user->name }}</p>
                            <time class="text-[11px] text-zinc-400" datetime="{{ $comment->created_at?->toAtomString() }}">{{ $comment->created_at?->diffForHumans() }}</time>
                        </div>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $comment->body }}</p>
                    </div>
                </div>
            @endforeach

            <form wire:submit="submit" class="flex items-center gap-2">
                @auth
                    <flux:avatar size="xs" :name="auth()->user()->name" :src="auth()->user()->avatarUrl()" circle class="shrink-0" />
                @endauth
                <flux:input wire:model="body" placeholder="{{ __('Aporta a la conversación…') }}" class="min-w-0 flex-1" />
                <flux:button type="submit" size="sm" variant="primary" icon="paper-airplane" aria-label="{{ __('Publicar comentario') }}" />
            </form>
            @error('body')
                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror
        </div>
    @endif
</div>
