<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\WhatsApp\Actions\GenerateWhatsAppPromotion;
use App\Domain\WhatsApp\Actions\SaveWhatsAppDraft;
use App\Domain\WhatsApp\Models\WhatsAppContent;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Copiloto de WhatsApp inicial (1.7 del TODO): plantillas de texto listas
 * para copiar y compartir. No genera nada con IA todavía (diferido hasta
 * elegir proveedor, ver docs/architecture/decisiones.md), no envía nada
 * automáticamente y no es un chat en vivo — solo produce texto editable.
 */
new #[Title('Copiloto de WhatsApp')] class extends Component {
    #[Locked]
    public int $businessId;

    public string $type = WhatsAppContent::PROMOCION;

    public ?int $productId = null;

    public string $tone = 'cercano';

    public string $generated = '';

    public bool $hasGenerated = false;

    /**
     * El middleware `business.team` solo corre en la carga inicial de la
     * página: las peticiones AJAX de Livewire (generar, guardar, borrar
     * borrador...) van al endpoint genérico `/livewire/update`, que no pasa
     * por esa ruta ni por ese middleware. `boot()` sí se ejecuta en cada
     * petición (inicial y subsecuentes), así que es el único lugar donde
     * fijar el team de forma confiable en todo el ciclo de vida del
     * componente — sin esto, cualquier acción después del primer render
     * pierde el contexto de equipo y falla con 403.
     */
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

        $this->businessId = $business->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::with('storefront')->findOrFail($this->businessId);
    }

    #[Computed]
    public function products()
    {
        return $this->business->products()->where('status', 'publicado')->get();
    }

    #[Computed]
    public function history()
    {
        return WhatsAppContent::query()
            ->where('business_id', $this->businessId)
            ->latest()
            ->take(20)
            ->get();
    }

    public function generate(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'type' => ['required', 'in:promocion,estado,respuesta,presentacion'],
            'productId' => ['nullable', 'integer', 'exists:products,id'],
            'tone' => ['required', 'in:cercano,formal'],
        ]);

        $product = $this->type === WhatsAppContent::PROMOCION && $this->productId
            ? Product::find($this->productId)
            : null;

        $this->generated = app(GenerateWhatsAppPromotion::class)->handle($this->business, $this->type, $product, $this->tone);
        $this->hasGenerated = true;
    }

    public function saveDraft(): void
    {
        $this->authorize('update', $this->business);

        if (blank($this->generated)) {
            return;
        }

        $product = $this->type === WhatsAppContent::PROMOCION && $this->productId
            ? Product::find($this->productId)
            : null;

        app(SaveWhatsAppDraft::class)->handle($this->business, $this->type, $product, $this->tone, $this->generated);

        unset($this->history);

        Flux::toast(variant: 'success', text: __('Borrador guardado.'));
    }

    public function reuse(int $draftId): void
    {
        $draft = WhatsAppContent::where('business_id', $this->businessId)->findOrFail($draftId);

        $this->generated = $draft->content;
        $this->hasGenerated = true;
    }

    public function deleteDraft(int $draftId): void
    {
        $this->authorize('update', $this->business);

        WhatsAppContent::where('business_id', $this->businessId)->where('id', $draftId)->delete();

        unset($this->history);
    }
}; ?>

<section class="mx-auto w-full max-w-2xl space-y-8">
    <div>
        <flux:heading size="xl">{{ __('Copiloto de WhatsApp') }}</flux:heading>
        <flux:subheading>
            {{ __('Genera un texto listo para copiar y compartir. Nada se envía automáticamente: tú decides cuándo y a quién.') }}
        </flux:subheading>
    </div>

    <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
        <flux:select wire:model.live="type" :label="__('¿Qué quieres generar?')">
            <flux:select.option value="promocion">{{ __('Promoción de un producto o servicio') }}</flux:select.option>
            <flux:select.option value="estado">{{ __('Estado de WhatsApp') }}</flux:select.option>
            <flux:select.option value="respuesta">{{ __('Respuesta a preguntas frecuentes') }}</flux:select.option>
            <flux:select.option value="presentacion">{{ __('Presentación del negocio') }}</flux:select.option>
        </flux:select>

        @if ($type === 'promocion')
            <flux:select wire:model="productId" :label="__('Producto o servicio (opcional)')">
                <flux:select.option value="">{{ __('Sin producto específico') }}</flux:select.option>
                @foreach ($this->products as $product)
                    <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <flux:select wire:model="tone" :label="__('Tono')">
            <flux:select.option value="cercano">{{ __('Cercano (con emoji)') }}</flux:select.option>
            <flux:select.option value="formal">{{ __('Formal (sin emoji)') }}</flux:select.option>
        </flux:select>

        <flux:button variant="primary" wire:click="generate" class="w-full">
            {{ __('Generar texto') }}
        </flux:button>
    </div>

    @if ($hasGenerated)
        <div class="space-y-3 rounded-2xl border border-amber-300 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-950">
            <flux:text class="text-sm font-medium">
                {{ __('Revisa y edita el texto antes de compartirlo. Merkamigo no verifica automáticamente su contenido.') }}
            </flux:text>

            <flux:textarea wire:model="generated" rows="6" />

            <div x-data class="flex flex-wrap gap-2">
                <flux:button
                    type="button"
                    variant="primary"
                    x-on:click="navigator.clipboard.writeText($wire.generated); $flux.toast('{{ __('Texto copiado') }}')"
                >
                    {{ __('Copiar') }}
                </flux:button>

                <flux:button
                    type="button"
                    variant="ghost"
                    icon="chat-bubble-left-right"
                    x-on:click="window.open('https://wa.me/?text=' + encodeURIComponent($wire.generated), '_blank')"
                >
                    {{ __('Abrir WhatsApp') }}
                </flux:button>

                <flux:button type="button" variant="ghost" wire:click="saveDraft">
                    {{ __('Guardar borrador') }}
                </flux:button>
            </div>
        </div>
    @endif

    <div>
        <flux:subheading class="mb-3">{{ __('Historial (últimos 20)') }}</flux:subheading>

        @if ($this->history->isEmpty())
            <x-states.empty title="{{ __('Todavía no has guardado borradores') }}" />
        @else
            <div class="space-y-2">
                @foreach ($this->history as $draft)
                    <div class="flex items-start justify-between gap-3 rounded-xl border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <div class="min-w-0">
                            <div class="font-medium">{{ ucfirst($draft->type) }} · {{ $draft->created_at->diffForHumans() }}</div>
                            <div class="truncate text-zinc-500">{{ \Illuminate\Support\Str::limit($draft->content, 80) }}</div>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <flux:button size="xs" variant="ghost" wire:click="reuse({{ $draft->id }})">{{ __('Reutilizar') }}</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="deleteDraft({{ $draft->id }})" wire:confirm="{{ __('¿Borrar este borrador?') }}">{{ __('Borrar') }}</flux:button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
