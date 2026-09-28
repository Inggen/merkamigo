<?php

use App\Domain\Messaging\Actions\SendBusinessMessage;
use App\Domain\Messaging\Models\BusinessConversation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::cliente', ['showChatWidget' => false])] #[Title('Mensajes')] class extends Component
{
    #[Locked]
    public ?int $conversationId = null;

    public string $body = '';

    public function mount(?BusinessConversation $conversation = null): void
    {
        if ($conversation) {
            abort_unless($conversation->canBeViewedBy(Auth::user()), 403);
            $this->conversationId = $conversation->id;
            $this->markAsRead($conversation);
        }
    }

    #[Computed]
    public function conversations()
    {
        return BusinessConversation::query()
            ->accessibleTo(Auth::user())
            ->with(['business', 'customer', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->withCount(['messages as unread_messages_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('sender_user_id', '!=', Auth::id())])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    #[Computed]
    public function conversation(): ?BusinessConversation
    {
        if (! $this->conversationId) {
            return null;
        }

        return BusinessConversation::query()
            ->accessibleTo(Auth::user())
            ->with(['business', 'customer', 'messages' => fn ($query) => $query->with('sender')->oldest()])
            ->findOrFail($this->conversationId);
    }

    public function send(): void
    {
        abort_unless($this->conversation, 404);

        app(SendBusinessMessage::class)->handle($this->conversation, Auth::user(), $this->body);

        $this->reset('body');
        unset($this->conversation, $this->conversations);
    }

    public function refreshMessages(): void
    {
        if ($this->conversation) {
            $this->markAsRead($this->conversation);
        }

        unset($this->conversation, $this->conversations);
    }

    private function markAsRead(BusinessConversation $conversation): void
    {
        $conversation->messages()
            ->where('sender_user_id', '!=', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}; ?>

<section class="mx-auto mt-4 flex h-[calc(100dvh-8rem)] w-full max-w-6xl overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900" wire:poll.5s="refreshMessages">
    <aside class="w-full border-r border-zinc-200 dark:border-zinc-700 md:w-80 {{ $this->conversation ? 'hidden md:block' : '' }}">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <flux:heading size="xl">{{ __('Mensajes') }}</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-500">{{ __('Conversaciones con clientes y negocios.') }}</flux:text>
        </div>

        <div class="h-[calc(100%-5.5rem)] overflow-y-auto">
            @forelse ($this->conversations as $item)
                @php
                    $viewingAsCustomer = $item->customer_user_id === auth()->id();
                    $name = $viewingAsCustomer ? $item->business->name : $item->customer->name;
                    $avatar = $viewingAsCustomer ? $item->business->logoUrl() : $item->customer->avatarUrl();
                    $lastMessage = $item->messages->first();
                @endphp
                <a
                    href="{{ route('messages.show', $item) }}"
                    wire:navigate
                    class="flex gap-3 border-b border-zinc-100 p-4 transition hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/70 {{ $conversationId === $item->id ? 'bg-brand-50 dark:bg-brand-950/40' : '' }}"
                >
                    <flux:avatar :src="$avatar" :name="$name" circle />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">{{ $name }}</p>
                            @if ($item->unread_messages_count > 0)
                                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-brand-600 px-1.5 text-xs font-semibold text-white">{{ $item->unread_messages_count }}</span>
                            @endif
                        </div>
                        @if ($item->context_label)
                            <p class="truncate text-xs text-brand-600 dark:text-brand-300">{{ $item->context_label }}</p>
                        @endif
                        <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $lastMessage?->body ?? __('Conversación nueva') }}
                        </p>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center">
                    <flux:icon.chat-bubble-left-right class="mx-auto size-8 text-zinc-300" />
                    <p class="mt-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Todavía no tienes conversaciones') }}</p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('Cuando contactes un negocio o recibas un mensaje, aparecerá aquí.') }}</p>
                </div>
            @endforelse
        </div>
    </aside>

    <div class="min-w-0 flex-1 {{ $this->conversation ? 'flex' : 'hidden md:flex' }} flex-col">
        @if ($this->conversation)
            @php
                $viewingAsCustomer = $this->conversation->customer_user_id === auth()->id();
                $name = $viewingAsCustomer ? $this->conversation->business->name : $this->conversation->customer->name;
            @endphp
            <header class="flex items-center gap-3 border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <a href="{{ route('messages.index') }}" wire:navigate class="inline-flex size-9 items-center justify-center rounded-full hover:bg-zinc-100 md:hidden dark:hover:bg-zinc-800" aria-label="{{ __('Volver a conversaciones') }}">
                    <flux:icon.chevron-left class="size-5" />
                </a>
                <div class="min-w-0 flex-1">
                    <flux:heading class="truncate">{{ $name }}</flux:heading>
                    @if ($this->conversation->context_label)
                        <a href="{{ $this->conversation->context_url }}" wire:navigate class="block truncate text-xs font-medium text-brand-600 hover:underline dark:text-brand-300">
                            {{ __('Sobre: :context', ['context' => $this->conversation->context_label]) }}
                        </a>
                    @endif
                </div>
            </header>

            <div class="flex-1 space-y-3 overflow-y-auto bg-zinc-50/70 p-4 dark:bg-zinc-950/40">
                @forelse ($this->conversation->messages as $message)
                    @php($own = $message->sender_user_id === auth()->id())
                    <div class="flex {{ $own ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $own ? 'rounded-br-sm bg-brand-600 text-white' : 'rounded-bl-sm bg-white text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100' }}">
                            <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                            <p class="mt-1 text-right text-[10px] {{ $own ? 'text-white/70' : 'text-zinc-400' }}">{{ $message->created_at->format('H:i') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="mx-auto mt-10 max-w-sm text-center">
                        <flux:icon.chat-bubble-oval-left-ellipsis class="mx-auto size-9 text-brand-500" />
                        <p class="mt-3 font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Inicia la conversación') }}</p>
                        <p class="mt-1 text-sm text-zinc-500">{{ __('Escribe tu consulta y el negocio recibirá una notificación.') }}</p>
                    </div>
                @endforelse
            </div>

            <form wire:submit="send" class="border-t border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-end gap-2">
                    <flux:textarea wire:model="body" rows="1" maxlength="2000" class="min-w-0 flex-1" placeholder="{{ __('Escribe un mensaje…') }}" />
                    <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="send">
                        {{ __('Enviar') }}
                    </flux:button>
                </div>
                @error('body')
                    <flux:text class="mt-1 text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
            </form>
        @else
            <div class="m-auto max-w-sm p-8 text-center">
                <flux:icon.chat-bubble-left-right class="mx-auto size-10 text-zinc-300" />
                <flux:heading class="mt-3">{{ __('Selecciona una conversación') }}</flux:heading>
            </div>
        @endif
    </div>
</section>
