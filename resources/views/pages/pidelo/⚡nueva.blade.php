<?php

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\PublishNeed;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Exceptions\IncompleteNeedException;
use App\Domain\Needs\Models\Need;
use Flux\Flux;
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
new #[Layout('layouts::cliente')] #[Title('Pídelo en Merkamigo')] class extends Component {
    use WithFileUploads;

    public ?int $needId = null;

    public string $title = '';
    public string $description = '';
    public ?int $municipality_id = null;
    public ?int $category_id = null;
    public ?string $zone = '';
    public ?string $budget = null;

    /** @var array<int, \Illuminate\Http\UploadedFile> */
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

<div class="mx-auto max-w-2xl px-6 py-8">
        <flux:heading size="xl">{{ __('Cuéntanos qué necesitas') }}</flux:heading>
        <flux:text class="mt-1 mb-6 text-zinc-500 dark:text-zinc-400">
            {{ __('Compártelo una vez y recibe propuestas de negocios cercanos por acá mismo.') }}
        </flux:text>

        @if ($savedAt)
            <div class="mb-4 flex items-center gap-1.5 text-sm text-zinc-400">
                <flux:icon.check-circle class="size-4" variant="outline" />
                {{ __('Guardado automáticamente a las :hora', ['hora' => $savedAt]) }}
            </div>
        @endif

        @if (! $previewing)
            <div class="space-y-4">
                <flux:input wire:model.live.debounce.900ms="title" :label="__('¿Qué necesitas?')" placeholder="{{ __('Ej: Torta de cumpleaños para el sábado') }}" required />
                <flux:textarea wire:model.live.debounce.900ms="description" :label="__('Cuéntanos más')" rows="4" placeholder="{{ __('Cantidad, fecha, presupuesto aproximado, cualquier detalle que ayude...') }}" />

                <flux:select wire:model.live="municipality_id" :label="__('Municipio')">
                    <flux:select.option value="">{{ __('Selecciona un municipio') }}</flux:select.option>
                    @foreach ($this->municipalities as $option)
                        <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model.live.debounce.900ms="zone" :label="__('Zona o barrio (opcional)')" />

                <flux:select wire:model.live="category_id" :label="__('Categoría (opcional)')">
                    <flux:select.option value="">{{ __('Sin categoría') }}</flux:select.option>
                    @foreach ($this->categories as $option)
                        <flux:select.option value="{{ $option->id }}">{{ $option->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model.live.debounce.900ms="budget" type="number" step="0.01" min="0" :label="__('Presupuesto aproximado (opcional)')" />

                <div>
                    <flux:text class="mb-2 text-sm font-medium">{{ __('Fotos (opcional)') }}</flux:text>
                    <input type="file" wire:model="photos" multiple accept="image/*" class="block w-full text-sm">

                    @if ($this->need?->media->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($this->need->media as $photo)
                                <img src="{{ $photo->url() }}" class="size-16 rounded-lg object-cover">
                            @endforeach
                        </div>
                    @endif
                </div>

                <flux:button variant="primary" class="w-full" wire:click="togglePreview">
                    {{ __('Revisar antes de publicar') }}
                </flux:button>
            </div>
        @else
            <div class="space-y-4 rounded-2xl border border-zinc-200 p-5 dark:border-zinc-700">
                <flux:heading size="lg">{{ $title ?: __('(Sin título todavía)') }}</flux:heading>
                <flux:text class="text-zinc-600 dark:text-zinc-300">{{ $description ?: __('(Sin descripción todavía)') }}</flux:text>

                <div class="flex flex-wrap gap-x-3 text-sm text-zinc-500 dark:text-zinc-400">
                    <span>{{ $this->municipalities->firstWhere('id', $municipality_id)?->name ?? __('Sin municipio') }}</span>
                    @if ($zone)
                        <span>{{ $zone }}</span>
                    @endif
                    @if ($budget)
                        <span>{{ __('Presupuesto: $:budget', ['budget' => number_format((float) $budget, 0, ',', '.')]) }}</span>
                    @endif
                </div>

                @if ($missing !== [])
                    <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                        <flux:text class="font-medium">{{ __('Te falta completar:') }}</flux:text>
                        <ul class="mt-2 list-inside list-disc text-sm">
                            @foreach ($missing as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex gap-3">
                    <flux:button variant="ghost" wire:click="togglePreview">{{ __('Editar información') }}</flux:button>
                    <flux:button variant="primary" class="flex-1" wire:click="publish">{{ __('Publicar solicitud') }}</flux:button>
                </div>
            </div>
        @endif
</div>
