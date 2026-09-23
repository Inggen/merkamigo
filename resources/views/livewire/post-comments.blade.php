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
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $comment->user->name }}</p>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $comment->body }}</p>
                    </div>
                </div>
            @endforeach

            <form wire:submit="submit" class="flex items-center gap-2">
                <flux:input wire:model="body" placeholder="{{ __('Escribe un comentario...') }}" class="flex-1" />
                <flux:button type="submit" size="sm" variant="primary">{{ __('Enviar') }}</flux:button>
            </form>
            @error('body')
                <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror
        </div>
    @endif
</div>
