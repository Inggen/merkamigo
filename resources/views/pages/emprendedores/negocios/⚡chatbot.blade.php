<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Actions\RemoveBusinessChatbotDocument;
use App\Domain\Storefronts\Actions\SaveBusinessChatbotDocument;
use App\Domain\Storefronts\Actions\SaveBusinessChatbotProfile;
use App\Domain\Storefronts\Models\BusinessChatbotProfile;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pedido del usuario: negocios con el chatbot IA (plan Emprendedor o el
 * add-on, `Business::canUseAiChatbot()`) le dan contexto propio al
 * asistente de su vitrina — un PDF, notas sueltas y el tono/jerga con la
 * que habla el negocio. Solo visible/accesible con esa entitlement, igual
 * que ya se valida en `DiscoveryController::chat`.
 */
new #[Title('Chatbot IA')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $tone = '';

    public string $extra_notes = '';

    public $document;

    public function boot(): void
    {
        if (isset($this->businessId)) {
            setPermissionsTeamId($this->businessId);
            Auth::user()?->unsetRelation('roles');
        }
    }

    public function mount(Business $business): void
    {
        setPermissionsTeamId($business->id);
        Auth::user()->unsetRelation('roles');

        $this->authorize('update', $business);
        abort_unless($business->canUseAiChatbot(), 403);

        $this->businessId = $business->id;

        $profile = $business->chatbotProfile;
        $this->tone = $profile?->tone ?? '';
        $this->extra_notes = $profile?->extra_notes ?? '';
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function profile(): ?BusinessChatbotProfile
    {
        return $this->business->chatbotProfile;
    }

    /**
     * Pedido del usuario: seguimiento de quién le escribió al chatbot y
     * qué se dijo. Las 20 conversaciones más recientes bastan para un
     * primer vistazo — no hay paginación todavía.
     */
    #[Computed]
    public function conversations()
    {
        return $this->business->chatConversations()->with('messages')->limit(20)->get();
    }

    public function saveProfile(): void
    {
        $this->authorize('update', $this->business);

        $data = $this->validate([
            'tone' => ['nullable', 'string', 'max:300'],
            'extra_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        app(SaveBusinessChatbotProfile::class)->handle($this->business, $data['tone'], $data['extra_notes']);

        unset($this->profile);
        Flux::toast(variant: 'success', text: __('Guardamos el tono y las notas de tu chatbot.'));
    }

    public function uploadDocument(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'document' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        try {
            app(SaveBusinessChatbotDocument::class)->handle($this->business, $this->document);
        } catch (InvalidArgumentException $e) {
            $this->addError('document', $e->getMessage());

            return;
        }

        $this->reset('document');
        unset($this->profile);
        Flux::toast(variant: 'success', text: __('Documento cargado. Tu chatbot ya puede usarlo para responder.'));
    }

    public function removeDocument(): void
    {
        $this->authorize('update', $this->business);

        $profile = $this->profile;

        if ($profile) {
            app(RemoveBusinessChatbotDocument::class)->handle($profile);
        }

        unset($this->profile);
        Flux::toast(variant: 'success', text: __('Quitamos el documento de tu chatbot.'));
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-8">
    <div class="flex items-center gap-1.5">
        <flux:heading size="xl">{{ __('Chatbot IA') }}</flux:heading>
        <flux:tooltip :content="__('Esto solo afecta las respuestas del chat con IA de tu vitrina — nunca cambia tus datos públicos (horario, dirección, productos, etc).')">
            <flux:icon.question-mark-circle class="size-4 shrink-0 text-zinc-400" variant="outline" />
        </flux:tooltip>
    </div>
    <flux:text class="text-zinc-500 dark:text-zinc-400">
        {{ __('Dale a tu chatbot más contexto sobre tu negocio y hazlo sonar como tú.') }}
    </flux:text>

    <form wire:submit="saveProfile" class="space-y-6 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <div>
            <flux:heading size="lg">{{ __('Cómo habla tu chatbot') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Cuéntale qué expresiones o forma de hablar usar — la jerga con la que la gente los reconoce.') }}
            </flux:text>
        </div>

        <flux:textarea
            wire:model="tone"
            :label="__('Tono y jerga')"
            :placeholder="__('Ej: llámalo mijito, sumercé o chinito, tono paisa cercano y bromista, cierra con ¡a la orden!')"
            rows="3"
        />

        <div>
            <flux:heading size="lg">{{ __('Notas sobre tu negocio') }}</flux:heading>
            <flux:text class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Cualquier dato que quieras que el chatbot conozca: historia del negocio, marcas que manejas, políticas, preguntas que te hacen seguido...') }}
            </flux:text>
            <flux:textarea wire:model="extra_notes" :placeholder="__('Ej: Llevamos 15 años en el barrio. Solo trabajamos con pedidos mínimos de $20.000. Los domingos cerramos temprano.')" rows="5" />
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
        </div>
    </form>

    <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <div>
            <flux:heading size="lg">{{ __('Documento con información de tu negocio') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Sube un PDF (catálogo, preguntas frecuentes, condiciones, lo que quieras) y tu chatbot lo consultará al responder.') }}
            </flux:text>
        </div>

        @if ($this->profile?->document_path)
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex min-w-0 items-center gap-3">
                    <flux:icon.document-text variant="outline" class="size-6 shrink-0 text-zinc-500 dark:text-zinc-400" />
                    <flux:text class="truncate font-medium text-zinc-800 dark:text-zinc-100">
                        {{ $this->profile->document_original_name }}
                    </flux:text>
                </div>

                <flux:button size="sm" variant="ghost" wire:click="removeDocument" wire:confirm="{{ __('¿Quitar este documento del chatbot?') }}">
                    {{ __('Quitar') }}
                </flux:button>
            </div>
        @endif

        <form wire:submit="uploadDocument" class="space-y-3">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                {{ $this->profile?->document_path ? __('Reemplazar documento (PDF)') : __('Subir documento (PDF)') }}
            </label>

            <input
                type="file"
                wire:model="document"
                accept="application/pdf"
                class="block w-full text-sm text-zinc-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-600 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-700 dark:text-zinc-300"
            >

            @error('document')
                <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
            @enderror

            <div wire:loading wire:target="document" class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Cargando…') }}
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" size="sm">{{ __('Subir') }}</flux:button>
            </div>
        </form>
    </div>

    <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <div>
            <flux:heading size="lg">{{ __('Conversaciones recientes') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Quién le ha escrito a tu chatbot y qué se dijo. Te avisamos por correo con un resumen cuando una conversación termina.') }}
            </flux:text>
        </div>

        @if ($this->conversations->isEmpty())
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Todavía no ha llegado ninguna conversación.') }}
            </flux:text>
        @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->conversations as $conversation)
                    <details class="group py-3">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3">
                            <div class="min-w-0">
                                <flux:text class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ $conversation->displayLabel() }}
                                </flux:text>
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $conversation->last_message_at->diffForHumans() }}
                                    &middot;
                                    {{ trans_choice(':count mensaje|:count mensajes', $conversation->messages->count(), ['count' => $conversation->messages->count()]) }}
                                </flux:text>
                            </div>
                            <flux:icon.chevron-down variant="outline" class="size-4 shrink-0 text-zinc-400 transition group-open:rotate-180" />
                        </summary>

                        @if ($conversation->summary)
                            <flux:text class="mt-2 block rounded-lg bg-brand-50 px-3 py-2 text-sm text-brand-800 dark:bg-brand-950 dark:text-brand-200">
                                {{ $conversation->summary }}
                            </flux:text>
                        @endif

                        <div class="mt-3 space-y-2">
                            @foreach ($conversation->messages as $message)
                                <div @class([
                                    'max-w-[85%] rounded-2xl px-3 py-2 text-sm',
                                    'ml-auto bg-brand-600 text-white' => $message->role === 'user',
                                    'mr-auto bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' => $message->role === 'assistant',
                                ])>
                                    {{ $message->content }}
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        @endif
    </div>
</section>
