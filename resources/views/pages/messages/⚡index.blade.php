<?php

use App\Domain\Messaging\Actions\DeleteBusinessConversation;
use App\Domain\Messaging\Actions\SendBusinessMessage;
use App\Domain\Messaging\Models\BusinessConversation;
use App\Domain\Moderation\Actions\SubmitReport;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::cliente', ['showChatWidget' => false, 'showSidebar' => true])] #[Title('Mensajes')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $conversationId = null;

    public string $body = '';

    public $attachment = null;

    public string $reportReason = 'contenido_inapropiado';

    public string $reportDetails = '';

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

        if ($this->attachment) {
            $this->validate(['attachment' => ['image', 'max:5120']]);
        }

        app(SendBusinessMessage::class)->handle($this->conversation, Auth::user(), $this->body, $this->attachment);

        $this->reset('body', 'attachment');
        unset($this->conversation, $this->conversations);
    }

    public function removeAttachment(): void
    {
        $this->reset('attachment');
    }

    public function deleteConversation(): void
    {
        abort_unless($this->conversation, 404);

        app(DeleteBusinessConversation::class)->handle($this->conversation, Auth::user());

        $this->conversationId = null;
        unset($this->conversation, $this->conversations);

        $this->redirectRoute('messages.index', navigate: true);
    }

    public function submitReport(): void
    {
        abort_unless($this->conversation, 404);

        $data = $this->validate([
            'reportReason' => ['required', 'in:contenido_inapropiado,informacion_falsa,spam,otro'],
            'reportDetails' => ['nullable', 'string', 'max:1000'],
        ]);

        app(SubmitReport::class)->handle(
            $this->conversation,
            $data['reportReason'],
            $data['reportDetails'] ?: null,
            Auth::user()->email,
        );

        $this->reset('reportDetails');
        $this->reportReason = 'contenido_inapropiado';

        $this->dispatch('report-submitted');
        Flux::toast(__('Gracias, revisaremos esta conversación.'));
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

        Auth::user()->unreadNotifications()
            ->where('data->type', 'business_message')
            ->where('data->conversation_id', $conversation->id)
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
                            @if ($lastMessage && blank($lastMessage->body) && $lastMessage->attachment_path)
                                {{ __('📷 Foto') }}
                            @else
                                {{ $lastMessage?->body ?? __('Conversación nueva') }}
                            @endif
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

                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" aria-label="{{ __('Más opciones') }}" />

                    <flux:menu>
                        <flux:menu.item icon="flag" x-on:click="$dispatch('modal-show', { name: 'report-conversation' })">
                            {{ __('Reportar conversación') }}
                        </flux:menu.item>
                        <flux:menu.item
                            icon="trash"
                            variant="danger"
                            wire:click="deleteConversation"
                            wire:confirm="{{ __('¿Eliminar esta conversación? Se borra para ambas partes. Si alguien vuelve a escribir, se restaura con su historial.') }}"
                        >
                            {{ __('Eliminar conversación') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </header>

            <flux:modal name="report-conversation" class="w-full !max-w-md" x-on:report-submitted.window="$flux.modal('report-conversation').close()">
                <div class="space-y-4">
                    <flux:heading size="lg">{{ __('Reportar conversación') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500">{{ __('Un moderador revisará el contenido de este hilo.') }}</flux:text>

                    <flux:select wire:model="reportReason" :label="__('Motivo')">
                        <flux:select.option value="contenido_inapropiado">{{ __('Contenido inapropiado') }}</flux:select.option>
                        <flux:select.option value="informacion_falsa">{{ __('Información falsa') }}</flux:select.option>
                        <flux:select.option value="spam">{{ __('Spam') }}</flux:select.option>
                        <flux:select.option value="otro">{{ __('Otro') }}</flux:select.option>
                    </flux:select>

                    <flux:textarea wire:model="reportDetails" :label="__('Detalles (opcional)')" rows="3" maxlength="1000" />

                    <flux:button variant="primary" class="w-full" wire:click="submitReport" wire:loading.attr="disabled" wire:target="submitReport">
                        {{ __('Enviar reporte') }}
                    </flux:button>
                </div>
            </flux:modal>

            <div class="flex-1 space-y-3 overflow-y-auto bg-zinc-50/70 p-4 dark:bg-zinc-950/40">
                @forelse ($this->conversation->messages as $message)
                    @php($own = $message->sender_user_id === auth()->id())
                    <div class="flex {{ $own ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $own ? 'rounded-br-sm bg-brand-600 text-white' : 'rounded-bl-sm bg-white text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100' }}">
                            @if ($message->attachmentUrl())
                                <a href="{{ $message->attachmentUrl() }}" target="_blank" rel="noopener" class="mb-1.5 block">
                                    <img src="{{ $message->attachmentUrl() }}" alt="{{ __('Foto adjunta') }}" class="max-h-64 rounded-lg object-cover" loading="lazy" />
                                </a>
                            @endif
                            @if (filled($message->body))
                                <p class="whitespace-pre-wrap break-words">{{ $message->body }}</p>
                            @endif
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
                @if ($attachment)
                    <div class="mb-2 flex items-center gap-2">
                        <img src="{{ $attachment->temporaryUrl() }}" alt="" class="size-14 rounded-lg object-cover" />
                        <flux:button type="button" variant="ghost" size="sm" icon="x-mark" wire:click="removeAttachment">
                            {{ __('Quitar foto') }}
                        </flux:button>
                    </div>
                @endif

                <div class="flex items-end gap-2">
                    <label class="inline-flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Adjuntar foto') }}">
                        <flux:icon.paper-clip class="size-5 text-zinc-500" />
                        <input type="file" wire:model="attachment" accept="image/*" class="hidden" />
                    </label>
                    <flux:textarea wire:model="body" rows="1" maxlength="2000" class="min-w-0 flex-1" placeholder="{{ __('Escribe un mensaje…') }}" />
                    <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="send">
                        {{ __('Enviar') }}
                    </flux:button>
                </div>
                @error('body')
                    <flux:text class="mt-1 text-sm text-red-600">{{ $message }}</flux:text>
                @enderror
                @error('attachment')
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
