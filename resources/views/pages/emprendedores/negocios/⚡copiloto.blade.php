<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\WhatsApp\Actions\GenerateWhatsAppPromotion;
use App\Domain\WhatsApp\Actions\SaveWhatsAppDraft;
use App\Domain\WhatsApp\Actions\SuggestWhatsAppContent;
use App\Domain\WhatsApp\Models\WhatsAppContent;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Copiloto de WhatsApp: genera texto editable para copiar, guardar y
 * compartir. Nunca envía mensajes automáticamente.
 */
new #[Title('Copiloto de WhatsApp')] class extends Component {
    #[Locked]
    public int $businessId;

    public string $type = WhatsAppContent::PROMOCION;

    public ?int $productId = null;

    public string $tone = 'cercano';

    public string $length = 'medio';

    public ?string $scheduledFor = null;

    public string $generated = '';

    public bool $hasGenerated = false;

    public ?string $faqDisponibilidad = null;

    public ?string $faqHorario = null;

    public ?string $faqDomicilio = null;

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
        $this->faqDisponibilidad = $business->faqAnswer('disponibilidad');
        $this->faqHorario = $business->faqAnswer('horario');
        $this->faqDomicilio = $business->faqAnswer('domicilio');
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

    /**
     * 4.4 del TODO: sugerencias basadas en métricas reales del negocio.
     */
    #[Computed]
    public function suggestions(): array
    {
        return app(SuggestWhatsAppContent::class)->handle($this->business);
    }

    #[Computed]
    public function history()
    {
        return WhatsAppContent::query()
            ->where('business_id', $this->businessId)
            ->orderByRaw('scheduled_for IS NULL, scheduled_for asc')
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
            'length' => ['required', 'in:corto,medio,largo'],
        ]);

        $product = $this->type === WhatsAppContent::PROMOCION && $this->productId
            ? Product::find($this->productId)
            : null;

        $this->generated = app(GenerateWhatsAppPromotion::class)->handle($this->business, $this->type, $product, $this->tone, $this->length);
        $this->hasGenerated = true;
    }

    public function selectType(string $type): void
    {
        abort_unless(in_array($type, [
            WhatsAppContent::PROMOCION,
            WhatsAppContent::ESTADO,
            WhatsAppContent::RESPUESTA,
            WhatsAppContent::PRESENTACION,
        ], true), 422);

        $this->type = $type;
    }

    public function selectTone(string $tone): void
    {
        abort_unless(in_array($tone, ['cercano', 'formal'], true), 422);

        $this->tone = $tone;
    }

    public function selectLength(string $length): void
    {
        abort_unless(in_array($length, ['corto', 'medio', 'largo'], true), 422);

        $this->length = $length;
    }

    public function saveDraft(): void
    {
        $this->authorize('update', $this->business);

        if (blank($this->generated)) {
            return;
        }

        $this->validate([
            'scheduledFor' => ['nullable', 'date'],
        ]);

        $product = $this->type === WhatsAppContent::PROMOCION && $this->productId
            ? Product::find($this->productId)
            : null;

        app(SaveWhatsAppDraft::class)->handle($this->business, $this->type, $product, $this->tone, $this->generated, $this->scheduledFor);

        $this->scheduledFor = null;
        unset($this->history);

        Flux::toast(variant: 'success', text: __('Borrador guardado.'));
    }

    public function saveFaqAnswers(): void
    {
        $this->authorize('update', $this->business);

        $this->validate([
            'faqDisponibilidad' => ['nullable', 'string', 'max:500'],
            'faqHorario' => ['nullable', 'string', 'max:500'],
            'faqDomicilio' => ['nullable', 'string', 'max:500'],
        ]);

        $this->business->update([
            'whatsapp_faq_answers' => array_filter([
                'disponibilidad' => $this->faqDisponibilidad ?: null,
                'horario' => $this->faqHorario ?: null,
                'domicilio' => $this->faqDomicilio ?: null,
            ]),
        ]);

        Flux::toast(variant: 'success', text: __('Respuestas guardadas.'));
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

<section class="w-full space-y-4 lg:space-y-5" x-data="{ tab: 'opportunities' }">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl" class="!text-3xl !font-bold !tracking-tight">{{ __('Copiloto de WhatsApp') }}</flux:heading>
                <flux:tooltip :content="__('Revisa siempre el texto generado antes de enviarlo: Merkamigo no verifica automáticamente que los datos sean correctos.')">
                    <flux:icon.information-circle class="size-5 shrink-0 text-zinc-400" variant="outline" />
                </flux:tooltip>
            </div>
            <flux:subheading class="mt-1">
                {{ __('Crea mensajes para vender, responder clientes y mantener activo tu negocio.') }}
            </flux:subheading>
        </div>
        <flux:modal.trigger name="copilot-message-composer">
            <flux:button variant="primary" icon="pencil-square">
                {{ __('Crear mensaje') }}
            </flux:button>
        </flux:modal.trigger>
    </header>

    <nav class="entrepreneur-card grid overflow-hidden p-1 sm:grid-cols-3" aria-label="{{ __('Secciones del Copiloto') }}">
        <button
            type="button"
            x-on:click="tab = 'opportunities'"
            x-bind:class="tab === 'opportunities' ? 'bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-200 font-semibold' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800'"
            class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm transition"
        >
            <flux:icon.light-bulb class="size-4" />
            {{ __('Oportunidades') }}
        </button>
        <button
            type="button"
            x-on:click="tab = 'quick-replies'"
            x-bind:class="tab === 'quick-replies' ? 'bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-200 font-semibold' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800'"
            class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm transition"
        >
            <flux:icon.chat-bubble-oval-left class="size-4" />
            {{ __('Respuestas rápidas') }}
        </button>
        <button
            type="button"
            x-on:click="tab = 'history'"
            x-bind:class="tab === 'history' ? 'bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-200 font-semibold' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800'"
            class="flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm transition"
        >
            <flux:icon.clock class="size-4" />
            {{ __('Historial') }}
        </button>
    </nav>

    @if (! empty($this->suggestions))
        <section x-show="tab === 'opportunities'" x-cloak class="entrepreneur-soft-card p-4 sm:p-5">
            <div class="mb-3 flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300">
                    <flux:icon.light-bulb class="size-6" />
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Oportunidades para hoy') }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Ideas basadas en la actividad de tu negocio para volver a conectar con tus clientes.') }}</p>
                </div>
            </div>
            <div class="space-y-2">
                @foreach ($this->suggestions as $suggestion)
                    <div class="flex flex-col gap-3 rounded-xl bg-white/85 px-4 py-3 ring-1 ring-zinc-200/70 sm:flex-row sm:items-center dark:bg-zinc-900/80 dark:ring-zinc-700">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ $suggestion }}</p>
                        </div>
                        <flux:modal.trigger name="copilot-message-composer">
                            <flux:button type="button" size="sm" variant="ghost" class="!border !border-brand-300 !text-brand-600 hover:!bg-brand-50 dark:!border-brand-700 dark:!text-brand-300">
                                {{ __('Crear promoción') }}
                            </flux:button>
                        </flux:modal.trigger>
                    </div>
                @endforeach
            </div>
        </section>
    @else
        <section x-show="tab === 'opportunities'" x-cloak class="entrepreneur-card p-6">
            <x-states.empty title="{{ __('No hay oportunidades pendientes para hoy') }}" />
        </section>
    @endif

    <flux:modal name="copilot-message-composer" class="w-full !max-w-5xl">
        <div class="mb-5">
            <div>
                <flux:heading size="lg">{{ __('Crear mensaje') }}</flux:heading>
                <flux:subheading>{{ __('Configura tu mensaje y revisa cómo se verá antes de compartirlo.') }}</flux:subheading>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
        <section class="entrepreneur-card p-5">
            <div class="mb-5 flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm">
                    <flux:icon.pencil class="size-5" />
                </div>
                <div>
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('¿Qué quieres comunicar?') }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Selecciona el objetivo y personaliza tu mensaje.') }}</p>
                </div>
            </div>

            <div class="space-y-5">
                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Objetivo del mensaje') }}</legend>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach ([
                            'promocion' => [__('Promoción'), 'megaphone'],
                            'respuesta' => [__('Respuesta'), 'chat-bubble-oval-left'],
                            'estado' => [__('Estado'), 'bolt'],
                            'presentacion' => [__('Presentación'), 'building-storefront'],
                        ] as $value => [$label, $icon])
                            <button
                                type="button"
                                @class([
                                    'flex min-h-10 w-full items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500',
                                    'border-brand-600 bg-brand-600 text-white' => $type === $value,
                                    'border-zinc-200 bg-white text-zinc-700 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $type !== $value,
                                ])
                                wire:click="selectType('{{ $value }}')"
                            >
                                <flux:icon :name="$icon" class="size-4 shrink-0" />
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </fieldset>

                @if ($type === 'promocion')
                    <flux:select wire:model="productId" :label="__('Producto o servicio (opcional)')">
                        <flux:select.option value="">{{ __('Sin producto específico') }}</flux:select.option>
                        @foreach ($this->products as $product)
                            <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endif

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Tono del mensaje') }}</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @class([
                                'min-h-10 w-full rounded-lg border px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500',
                                'border-brand-600 bg-brand-600 text-white' => $tone === 'cercano',
                                'border-zinc-200 bg-white text-zinc-700 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $tone !== 'cercano',
                            ])
                            wire:click="selectTone('cercano')"
                        >
                            {{ __('Cercano 😊') }}
                        </button>
                        <button
                            type="button"
                            @class([
                                'min-h-10 w-full rounded-lg border px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500',
                                'border-brand-600 bg-brand-600 text-white' => $tone === 'formal',
                                'border-zinc-200 bg-white text-zinc-700 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $tone !== 'formal',
                            ])
                            wire:click="selectTone('formal')"
                        >
                            {{ __('Profesional') }}
                        </button>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Longitud del mensaje') }}</legend>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['corto' => __('Corta'), 'medio' => __('Media'), 'largo' => __('Larga')] as $value => $label)
                            <button
                                type="button"
                                @class([
                                    'min-h-10 w-full rounded-lg border px-2 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500',
                                    'border-brand-600 bg-brand-600 text-white' => $length === $value,
                                    'border-zinc-200 bg-white text-zinc-700 hover:border-brand-200 hover:bg-brand-50 hover:text-brand-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200' => $length !== $value,
                                ])
                                wire:click="selectLength('{{ $value }}')"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </fieldset>

                <flux:button variant="primary" icon="sparkles" wire:click="generate" class="!w-full !justify-center">
                    {{ $hasGenerated ? __('Generar otra versión') : __('Generar mensaje') }}
                </flux:button>
                <p class="text-center text-xs text-zinc-400">{{ __('Puedes generar varias versiones hasta encontrar la ideal.') }}</p>
            </div>
        </section>

        <section class="entrepreneur-card overflow-hidden p-5">
            <div class="mb-4 flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500 text-white shadow-sm">
                    <flux:icon.chat-bubble-left-right class="size-5" />
                </div>
                <div>
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Vista previa') }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Así se verá tu mensaje en WhatsApp.') }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-[#efeae2] shadow-inner">
                <div class="flex items-center gap-3 bg-[#075e54] px-4 py-3 text-white">
                    <div class="flex size-9 items-center justify-center rounded-full bg-white font-bold text-brand-600">M</div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $this->business->name }}</p>
                        <p class="text-xs text-emerald-100">{{ __('en línea') }}</p>
                    </div>
                    <flux:icon.phone class="size-5" />
                </div>
                <div class="min-h-72 bg-[radial-gradient(circle_at_20%_20%,rgba(255,255,255,.72),transparent_32%),radial-gradient(circle_at_80%_55%,rgba(208,230,211,.65),transparent_34%)] p-5">
                    <div class="ml-auto max-w-[92%] rounded-2xl rounded-tr-sm bg-[#d9fdd3] p-3 text-sm text-zinc-800 shadow-sm">
                        @if ($hasGenerated)
                            <flux:textarea wire:model="generated" rows="8" class="whatsapp-preview-textarea" />
                        @else
                            <p class="whitespace-pre-line leading-relaxed">{{ __("¡Hola! 👋\nCrea tu mensaje y aquí podrás revisarlo antes de compartirlo con tus clientes.") }}</p>
                        @endif
                        <p class="mt-2 text-right text-[11px] text-zinc-500">{{ now()->format('g:i a') }} <span class="text-sky-500">✓✓</span></p>
                    </div>
                </div>
            </div>

            @if ($hasGenerated)
                <div class="mt-3 space-y-3" x-data>
                    <flux:input type="date" wire:model="scheduledFor" :label="__('Fecha sugerida para usarlo (opcional)')" />
                    <div class="grid grid-cols-3 gap-2">
                        <flux:button type="button" size="sm" icon="document-duplicate" variant="ghost" class="!justify-center" x-on:click="navigator.clipboard.writeText($wire.generated); $flux.toast('{{ __('Texto copiado') }}')">
                            {{ __('Copiar') }}
                        </flux:button>
                        <flux:button type="button" size="sm" icon="chat-bubble-left-right" variant="ghost" class="!justify-center" x-on:click="window.open('https://wa.me/?text=' + encodeURIComponent($wire.generated), '_blank')">
                            {{ __('WhatsApp') }}
                        </flux:button>
                        <flux:button type="button" size="sm" icon="bookmark" variant="ghost" class="!justify-center" wire:click="saveDraft">
                            {{ __('Guardar') }}
                        </flux:button>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Revisa y edita el texto antes de compartirlo. Nada se envía automáticamente.') }}</p>
                </div>
            @endif
        </section>
        </div>
    </flux:modal>

    <div>
        <section x-show="tab === 'quick-replies'" x-cloak class="entrepreneur-card p-5">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                        <flux:icon.chat-bubble-oval-left-ellipsis class="size-5" />
                    </div>
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Respuestas rápidas') }}</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Textos útiles para responder más rápido.') }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <flux:textarea wire:model="faqHorario" :label="__('Horarios')" rows="2" />
                <flux:textarea wire:model="faqDomicilio" :label="__('Domicilios')" rows="2" />
                <flux:textarea wire:model="faqDisponibilidad" :label="__('Disponibilidad')" rows="2" />
                <flux:button type="button" variant="primary" wire:click="saveFaqAnswers" class="!w-full !justify-center">
                    {{ __('Guardar respuestas') }}
                </flux:button>
            </div>
        </section>

        <section x-show="tab === 'history'" x-cloak class="entrepreneur-card p-5">
            <div class="mb-4 flex items-start gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                    <flux:icon.clock class="size-5" />
                </div>
                <div>
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Historial reciente') }}</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tus últimos mensajes guardados.') }}</p>
                </div>
            </div>

            @if ($this->history->isEmpty())
                <x-states.empty title="{{ __('Todavía no has guardado borradores') }}" />
            @else
                <div class="space-y-2">
                    @foreach ($this->history as $draft)
                        <div class="flex items-start justify-between gap-3 rounded-xl border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                    {{ ucfirst($draft->type) }} · {{ $draft->created_at->diffForHumans() }}
                                    @if ($draft->scheduled_for)
                                        <flux:badge size="sm" color="amber">{{ __('Para el :date', ['date' => $draft->scheduled_for->translatedFormat('d \\d\\e F')]) }}</flux:badge>
                                    @endif
                                </div>
                                <div class="truncate text-zinc-500">{{ \Illuminate\Support\Str::limit($draft->content, 80) }}</div>
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click="reuse({{ $draft->id }})" aria-label="{{ __('Reutilizar') }}" />
                                <flux:button size="xs" variant="ghost" icon="trash" wire:click="deleteDraft({{ $draft->id }})" wire:confirm="{{ __('¿Borrar este borrador?') }}" aria-label="{{ __('Borrar') }}" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</section>
