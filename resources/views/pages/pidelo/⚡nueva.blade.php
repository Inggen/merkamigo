<?php

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\PublishNeed;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Exceptions\IncompleteNeedException;
use App\Domain\Needs\Models\Need;
use Flux\Flux;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Pídelo en Merkamigo" (E-equivalente del Cliente, 2.1 del TODO): un
 * formulario con borrador automático y vista previa antes de publicar,
 * igual en espíritu al wizard de "Mi Merkamigo en cinco minutos" pero sin
 * pasos — 2.1 no exige un wizard, solo borrador + vista previa +
 * consentimiento explícito para publicar.
 *
 * `#[Layout('layouts::cliente')]` porque el layout por defecto de las
 * páginas Livewire de una sola vista es `layouts::app` (el shell con
 * sidebar del Emprendedor, ver `config('livewire.component_layout')`) —
 * esta es una página del Cliente y necesita el encabezado propio.
 */
new #[Layout('layouts::cliente')] #[Title('Pídelo en Merkamigo')] class extends Component
{
    use WithFileUploads;

    public ?int $needId = null;

    public string $title = '';

    public string $description = '';

    public ?int $municipality_id = null;

    public ?int $category_id = null;

    public ?string $zone = '';

    public ?string $budget = null;

    /** @var array<int, UploadedFile> */
    public array $photos = [];

    public bool $previewing = false;

    /** @var array<int, string> */
    public array $missing = [];

    public ?string $savedAt = null;

    /**
     * Sin segmento de ruta con el id: recupera el borrador más reciente del
     * comprador si existe (2.1 del TODO: "el borrador se recupera al
     * abandonar y regresar"), o empieza uno nuevo. Editar una solicitud ya
     * publicada ocurre desde `/mis-solicitudes/{id}`, no aquí.
     */
    public function mount(): void
    {
        $draft = Auth::user()->needs()->where('status', Need::BORRADOR)->latest()->first();

        if ($draft) {
            $this->needId = $draft->id;
            $this->title = $draft->title;
            $this->description = $draft->description;
            $this->municipality_id = $draft->municipality_id;
            $this->category_id = $draft->category_id;
            $this->zone = $draft->zone;
            $this->budget = $draft->budget !== null ? (string) $draft->budget : null;

            return;
        }

        $slug = request()->cookie('municipio');
        $preferred = $slug ? Municipality::where('slug', $slug)->where('is_active', true)->first() : null;
        $this->municipality_id = $preferred?->id;

        // Pedido del usuario: el asistente general del inicio puede armar
        // una solicitud a partir de la conversación y traer al comprador
        // aquí ya con los datos listos para revisar antes de publicar —
        // solo aplica sin borrador existente, para no pisar uno en curso.
        $this->title = (string) request()->query('titulo', $this->title);
        $this->description = (string) request()->query('descripcion', $this->description);

        $categorySlug = request()->query('categoria');
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->where('is_active', true)->first();
            $this->category_id = $category?->id ?? $this->category_id;
        }
    }

    #[Computed]
    public function need(): ?Need
    {
        return $this->needId ? Need::with('media')->find($this->needId) : null;
    }

    #[Computed]
    public function municipalities()
    {
        return Municipality::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)->orderBy('position')->get();
    }

    public function updated(string $property): void
    {
        if ($property === 'photos') {
            return;
        }

        $this->saveDraft([]);
    }

    public function updatedPhotos(): void
    {
        if ($this->photos === []) {
            return;
        }

        $this->saveDraft($this->photos);
        $this->reset('photos');
    }

    public function togglePreview(): void
    {
        $this->previewing = ! $this->previewing;
    }

    public function publish(): void
    {
        $need = $this->need ?? $this->saveDraft([]);

        try {
            app(PublishNeed::class)->handle($need, Auth::user());
            Flux::toast(variant: 'success', text: __('¡Tu solicitud está publicada!'));
            $this->redirect(route('mis-solicitudes.show', $need), navigate: true);
        } catch (IncompleteNeedException $e) {
            $this->missing = $e->missing;
        }
    }

    private function saveDraft(array $photos): Need
    {
        $need = app(SaveNeedDraft::class)->handle(Auth::user(), $this->need, [
            'title' => $this->title,
            'description' => $this->description,
            'municipality_id' => $this->municipality_id,
            'category_id' => $this->category_id,
            'zone' => $this->zone,
            'budget' => $this->budget,
        ], $photos);

        $this->needId = $need->id;
        $this->savedAt = now()->format('H:i');
        unset($this->need);

        return $need;
    }
}; ?>

<div class="mx-auto max-w-7xl px-6 py-8 lg:px-8">
    <div class="mx-auto max-w-6xl">
        <div class="mb-8">
            <div class="inline-flex items-center gap-2 rounded-full border border-brand-300 bg-white px-4 py-2 text-sm font-semibold text-brand-700 shadow-sm">
                <flux:icon.shopping-bag class="size-4" variant="outline" />
                {{ __('Pídelo en Merkamigo') }}
            </div>

            <flux:heading class="mt-4 text-xl font-bold tracking-tight text-zinc-950 sm:text-2xl">
                {{ __('Cuéntanos qué necesitas') }}
            </flux:heading>
            <flux:text class="mt-3 text-md text-zinc-500 dark:text-zinc-400">
                {{ __('Compártelo una vez y recibe propuestas de negocios cercanos por acá mismo.') }}
            </flux:text>

            @if ($savedAt)
                <div class="mt-4 flex items-center gap-1.5 text-sm text-zinc-400">
                    <flux:icon.check-circle class="size-4" variant="outline" />
                    {{ __('Guardado automáticamente a las :hora', ['hora' => $savedAt]) }}
                </div>
            @endif
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px] xl:items-start">
            <div>
                @if (! $previewing)
                    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="space-y-6 p-6 sm:p-8">
                            <section class="space-y-5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">1</span>
                                    <h2 class="text-lg font-semibold tracking-tight text-zinc-950 dark:text-zinc-100">{{ __('Describe lo que buscas') }}</h2>
                                </div>

                                <flux:input wire:model.live.debounce.900ms="title" :label="__('¿Qué necesitas?')" placeholder="{{ __('Ej: Torta de cumpleaños para el sábado') }}" required />
                                <flux:textarea wire:model.live.debounce.900ms="description" :label="__('Cuéntanos más')" rows="4" placeholder="{{ __('Cantidad, fecha, presupuesto aproximado, cualquier detalle que ayude...') }}" />

                                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Entre más detalles compartas, mejores serán las propuestas.') }}
                                </flux:text>
                            </section>

                            <div class="border-t border-zinc-200 dark:border-zinc-800"></div>

                            <section class="space-y-5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">2</span>
                                    <h2 class="text-lg font-semibold tracking-tight text-zinc-950 dark:text-zinc-100">{{ __('Ubicación y categoría') }}</h2>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <flux:select wire:model.live="municipality_id" :label="__('Municipio')">
                                        <flux:select.option value="">{{ __('Selecciona un municipio') }}</flux:select.option>
                                        @foreach ($this->municipalities as $option)
                                            <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                                        @endforeach
                                    </flux:select>

                                    <div>
                                        <div class="mb-2 flex items-center gap-2">
                                            <label class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Zona o barrio') }}</label>
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ __('Opcional') }}</span>
                                        </div>
                                        <flux:input wire:model.live.debounce.900ms="zone" placeholder="{{ __('Ej: Centro') }}" />
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Categoría') }}</label>
                                        <flux:select wire:model.live="category_id" required>
                                            <flux:select.option value="">{{ __('Selecciona una categoría') }}</flux:select.option>
                                            @foreach ($this->categories as $option)
                                                <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>

                                    <div>
                                        <div class="mb-2 flex items-center gap-2">
                                            <label class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ __('Presupuesto aproximado') }}</label>
                                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ __('Opcional') }}</span>
                                        </div>
                                        <flux:input wire:model.live.debounce.900ms="budget" type="number" step="0.01" min="0" placeholder="{{ __('Ej: 80000') }}" />
                                    </div>
                                </div>
                            </section>

                            <div class="border-t border-zinc-200 dark:border-zinc-800"></div>

                            <section class="space-y-5">
                                <div class="flex items-center gap-3">
                                    <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">3</span>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-lg font-semibold tracking-tight text-zinc-950 dark:text-zinc-100">{{ __('Agrega fotos') }}</h2>
                                        <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ __('Opcional') }}</span>
                                    </div>
                                </div>

                                <label
                                    x-data="{ isDragging: false }"
                                    x-on:dragenter.prevent="isDragging = true"
                                    x-on:dragover.prevent="isDragging = true"
                                    x-on:dragleave.prevent="isDragging = false"
                                    x-on:drop.prevent="
                                        isDragging = false;
                                        const files = Array.from($event.dataTransfer?.files || []).filter(file => file.type.startsWith('image/'));
                                        if (! files.length) return;

                                        const transfer = new DataTransfer();
                                        files.forEach(file => transfer.items.add(file));

                                        $refs.photoInput.files = transfer.files;
                                        $refs.photoInput.dispatchEvent(new Event('change', { bubbles: true }));
                                    "
                                    x-bind:class="isDragging ? 'border-brand-500 bg-brand-50/40 dark:border-brand-400 dark:bg-brand-500/10' : ''"
                                    class="block cursor-pointer rounded-xl border-2 border-dashed border-zinc-300 bg-white px-6 py-10 text-center transition hover:border-brand-400 hover:bg-brand-50/30 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-brand-500 dark:hover:bg-zinc-900"
                                >
                                    <span class="mx-auto mb-4 flex size-16 items-center justify-center rounded-3xl bg-zinc-100 text-brand-600 dark:bg-zinc-800 dark:text-brand-400">
                                        <flux:icon.photo class="size-8" variant="outline" />
                                    </span>
                                    <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Arrastra tus fotos aquí') }}</div>
                                    <div class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ __('o haz clic para elegir archivos · JPG, PNG o WEBP') }}
                                    </div>
                                    <input x-ref="photoInput" type="file" wire:model="photos" multiple accept="image/*" class="sr-only">
                                </label>

                                <div wire:loading wire:target="photos" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400">
                                    {{ __('Cargando fotos...') }}
                                </div>

                                @if ($photos !== [])
                                    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <flux:text class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                                                {{ trans_choice(':count foto lista para adjuntar|:count fotos listas para adjuntar', count($photos), ['count' => count($photos)]) }}
                                            </flux:text>
                                            <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Se guardarán automáticamente en tu borrador.') }}</flux:text>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            @foreach ($photos as $photo)
                                                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800">
                                                    <img src="{{ $photo->temporaryUrl() }}" class="aspect-square h-full w-full object-cover" alt="{{ __('Vista previa de la foto') }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($this->need?->media->isNotEmpty())
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($this->need->media as $photo)
                                            <img src="{{ $photo->url() }}" class="size-20 rounded-2xl object-cover">
                                        @endforeach
                                    </div>
                                @endif

                                @error('photos')
                                    <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
                                @enderror
                            </section>
                        </div>

                        <div class="border-t border-zinc-200 bg-zinc-50/80 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-900/60 sm:px-8">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div class="flex items-start gap-3">
                                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                        <flux:icon.shield-check class="size-5" variant="outline" />
                                    </span>
                                    <div class="text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                        {{ __('Podrás revisar toda la información antes de publicarla.') }}
                                    </div>
                                </div>

                                <flux:button variant="primary" class="w-full justify-center rounded-2xl px-6 py-4 text-base font-semibold lg:w-auto lg:min-w-96" wire:click="togglePreview">
                                    {{ __('Revisar antes de publicar') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl  border border-zinc-200 bg-white p-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)] dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
                        <div class="mb-6 flex items-center gap-3">
                            <span class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">✓</span>
                            <h2 class="text-xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-100">{{ __('Revisa tu solicitud') }}</h2>
                        </div>

                        <div class="space-y-4 rounded-3xl border border-zinc-200 p-5 dark:border-zinc-700">
                            <flux:heading size="lg">{{ $title ?: __('(Sin título todavía)') }}</flux:heading>
                            <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $description ?: __('(Sin descripción todavía)') }}</flux:text>

                            <div class="flex flex-wrap gap-x-3 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <span>{{ $this->municipalities->firstWhere('id', $municipality_id)?->name ?? __('Sin municipio') }}</span>
                                @if ($zone)
                                    <span>{{ $zone }}</span>
                                @endif
                                @if ($budget)
                                    <span>{{ __('Presupuesto: $:budget', ['budget' => number_format((float) $budget, 0, ',', '.')]) }}</span>
                                @endif
                            </div>

                            @if ($missing !== [])
                                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                                    <flux:text class="font-medium">{{ __('Te falta completar:') }}</flux:text>
                                    <ul class="mt-2 list-inside list-disc text-sm">
                                        @foreach ($missing as $item)
                                            <li>{{ $item }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="flex flex-col gap-3 sm:flex-row">
                                <flux:button variant="ghost" class="justify-center rounded-2xl" wire:click="togglePreview">{{ __('Editar información') }}</flux:button>
                                <flux:button variant="primary" class="flex-1 justify-center rounded-2xl" wire:click="publish">{{ __('Publicar solicitud') }}</flux:button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <aside class="space-y-5 xl:sticky xl:top-24 xl:self-start">
                <div class="rounded-xl border border-zinc-200 bg-gradient-to-b from-[#fffaf3] to-white p-5 dark:border-zinc-800 dark:from-zinc-900 dark:to-zinc-900">
                    <div class="mb-5 flex items-center gap-3">
                        <span class="flex size-12 items-center justify-center rounded-full bg-amber-100 text-amber-500 dark:bg-amber-500/10 dark:text-amber-300">
                            <flux:icon.light-bulb class="size-6" variant="outline" />
                        </span>
                        <h3 class="text-lg font-semibold tracking-tight text-zinc-950 dark:text-zinc-100">{{ __('Recibe mejores propuestas') }}</h3>
                    </div>

                    <div class="space-y-0">
                        @foreach ([
                            __('Explica qué necesitas y para cuándo.'),
                            __('Indica una zona de referencia.'),
                            __('Agrega fotos si tienes un ejemplo.'),
                        ] as $tip)
                            <div class="flex items-center gap-3 border-t border-zinc-200 py-4 first:border-t-0 first:pt-0 last:pb-0 dark:border-zinc-800">
                                <span class="flex size-8 items-center justify-center rounded-full bg-white text-brand-600 shadow-sm dark:bg-zinc-800 dark:text-brand-400">
                                    <flux:icon.check class="size-4" variant="outline" />
                                </span>
                                <p class="text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $tip }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="text-lg font-semibold tracking-tight text-zinc-950 dark:text-zinc-100">{{ __('¿Qué pasará después?') }}</h3>

                    <div class="mt-6 space-y-6">
                        @foreach ([
                            __('Publicas tu solicitud fácilmente'),
                            __('Los negocios cercanos responden'),
                            __('Eliges la propuesta que prefieras'),
                        ] as $index => $step)
                            <div class="relative flex items-start gap-4">
                                @if (! $loop->last)
                                    <span class="absolute left-[18px] top-10 h-12 w-px border-l border-dashed border-zinc-300 dark:border-zinc-700"></span>
                                @endif
                                <span class="relative z-10 flex size-8 items-center justify-center rounded-full border-2 border-brand-500 bg-white text-sm font-semibold text-brand-600 dark:bg-zinc-900 dark:text-brand-400">
                                    {{ $index + 1 }}
                                </span>
                                <p class="pt-1 text-sm leading-7 text-zinc-700 dark:text-zinc-300">{{ $step }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
