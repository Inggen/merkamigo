<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Actions\CreateStory;
use App\Domain\Social\Actions\DeleteStory;
use App\Domain\Social\Actions\GenerateSocialSalesCopy;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Tu estado" (Fase 3 del TODO social, Sprint 3): crear y listar estados
 * del negocio. Duran 24h — no hay edición, solo crear/eliminar antes de
 * tiempo.
 */
new #[Title('Estados')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $type = 'imagen';

    public ?string $caption = '';

    public $photo;

    public ?int $product_id = null;

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
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function activeStories()
    {
        return $this->business->activeStories()->with('product')->latest()->get();
    }

    #[Computed]
    public function availableProducts()
    {
        return $this->business->products()->where('status', 'publicado')->orderBy('name')->get();
    }

    public function save(): void
    {
        $this->authorize('update', $this->business);

        if (! $this->photo) {
            $this->addError('photo', __('Agrega una foto para tu estado.'));

            return;
        }

        try {
            app(CreateStory::class)->handle($this->business, [
                'type' => $this->type,
                'caption' => $this->caption,
                'product_id' => $this->product_id,
            ], $this->photo, Auth::user());
        } catch (ValidationException $e) {
            $this->addError('caption', $e->validator->errors()->first());

            return;
        }

        $this->reset(['caption', 'photo', 'product_id']);
        $this->type = 'imagen';

        unset($this->activeStories);

        Flux::modal('create-story')->close();
        Flux::toast(variant: 'success', text: __('¡Estado publicado! Estará visible por 24 horas.'));
    }

    public function removePhoto(): void
    {
        $this->reset('photo');
        $this->resetErrorBag('photo');
    }

    public function suggestWithAi(): void
    {
        $this->authorize('update', $this->business);

        $suggestion = app(GenerateSocialSalesCopy::class)->handle($this->business, 'estado', [
            'text' => $this->caption,
            'type' => $this->type,
            'product_ids' => $this->product_id ? [$this->product_id] : [],
        ]);

        if (! $suggestion) {
            Flux::toast(variant: 'warning', text: __('No fue posible generar una sugerencia.'));

            return;
        }

        $this->caption = $suggestion;
        Flux::toast(text: __('Borrador generado. Revísalo antes de publicar.'));
    }

    public function delete(int $storyId): void
    {
        $this->authorize('update', $this->business);

        $story = $this->business->stories()->findOrFail($storyId);

        app(DeleteStory::class)->handle($story, Auth::user());

        unset($this->activeStories);

        Flux::toast(variant: 'success', text: __('Estado eliminado.'));
    }
}; ?>

<section class="w-full space-y-6" x-data="{ previewStory: null }" x-on:keydown.escape.window="previewStory = null">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl" class="!text-3xl !font-bold !tracking-tight">{{ __('Estados') }}</flux:heading>
            <flux:subheading class="mt-1">{{ __('Contenido que dura 24 horas — ideal para promociones del día o novedades rápidas.') }}</flux:subheading>
        </div>
        <flux:modal.trigger name="create-story">
            <flux:button variant="primary" icon="plus" class="!justify-center">
                {{ __('Crear nuevo estado') }}
            </flux:button>
        </flux:modal.trigger>
    </header>

    <section class="entrepreneur-card p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Estados activos') }}</h2>
                <span class="inline-flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600 dark:bg-brand-950 dark:text-brand-300">
                    {{ $this->activeStories->count() }}
                </span>
            </div>
            <span class="hidden items-center gap-2 text-sm text-zinc-500 sm:inline-flex dark:text-zinc-400">
                {{ __('Más recientes') }}
                <flux:icon.chevron-down class="size-4" />
            </span>
        </div>

        <div class="space-y-3">
            @forelse ($this->activeStories as $story)
                <article class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-3 sm:flex-row sm:items-center dark:border-zinc-700 dark:bg-zinc-900">
                    <button
                        type="button"
                        x-on:click="previewStory = {{ $story->id }}"
                        class="group relative h-28 w-full shrink-0 overflow-hidden rounded-xl bg-zinc-950 sm:w-44"
                        aria-label="{{ __('Previsualizar estado') }}"
                    >
                        <img src="{{ $story->imageUrl() }}" class="size-full object-cover transition duration-300 group-hover:scale-105" alt="{{ $story->caption ?: __('Estado de :business', ['business' => $this->business->name]) }}">
                        <span class="absolute inset-x-2 bottom-2 inline-flex items-center justify-center gap-1.5 rounded-full bg-black/65 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-sm">
                            <flux:icon.eye class="size-4" />
                            {{ __('Vista previa') }}
                        </span>
                    </button>

                    <div class="min-w-0 flex-1">
                        <h3 class="truncate font-semibold text-zinc-900 dark:text-white">
                            {{ $story->caption ?: __('Estado sin texto') }}
                        </h3>
                        @if ($story->product)
                            <p class="mt-1 truncate text-sm text-brand-600 dark:text-brand-300">{{ $story->product->name }}</p>
                        @endif
                        <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon.chart-bar class="size-4" />
                                {{ __(':count vistas', ['count' => $story->views_count]) }}
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <flux:icon.clock class="size-4" />
                                {{ __('Expira :time', ['time' => $story->expires_at->diffForHumans()]) }}
                            </span>
                        </div>
                    </div>

                    <div class="flex shrink-0 gap-2 self-end sm:self-auto">
                        <flux:button type="button" variant="ghost" icon="eye" x-on:click="previewStory = {{ $story->id }}" aria-label="{{ __('Previsualizar estado') }}" />
                        <flux:button type="button" variant="ghost" icon="trash" wire:click="delete({{ $story->id }})" wire:confirm="{{ __('¿Eliminar este estado?') }}" aria-label="{{ __('Eliminar estado') }}" />
                    </div>
                </article>

                <template x-teleport="body">
                    <div
                        x-show="previewStory === {{ $story->id }}"
                        x-cloak
                        x-transition.opacity
                        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/95 p-3 sm:p-6"
                        role="dialog"
                        aria-modal="true"
                        aria-label="{{ __('Vista previa del estado') }}"
                    >
                        <button type="button" x-on:click="previewStory = null" class="absolute right-4 top-4 z-20 flex size-11 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" aria-label="{{ __('Cerrar') }}">
                            <flux:icon.x-mark class="size-7" />
                        </button>

                        <div class="relative flex h-full max-h-[900px] w-full max-w-md flex-col overflow-hidden rounded-3xl bg-zinc-950 shadow-2xl ring-1 ring-white/15">
                            <div class="absolute inset-x-0 top-0 z-10 bg-gradient-to-b from-black/75 to-transparent px-4 pb-10 pt-3 text-white">
                                <div class="mb-3 h-1 rounded-full bg-white"></div>
                                <div class="flex items-center gap-2.5">
                                    <div class="flex size-9 items-center justify-center rounded-full bg-white font-bold text-brand-600">M</div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold">{{ $this->business->name }}</p>
                                        <p class="text-xs text-white/70">{{ $story->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>

                            <img src="{{ $story->imageUrl() }}" class="min-h-0 flex-1 object-cover" alt="{{ $story->caption ?: __('Vista previa del estado') }}">

                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/55 to-transparent px-5 pb-5 pt-24 text-white">
                                @if ($story->caption)
                                    <p class="text-base font-medium leading-relaxed">{{ $story->caption }}</p>
                                @endif
                                @if ($story->product)
                                    <div class="mt-3 inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-semibold text-zinc-900">
                                        <flux:icon.cube class="size-4" />
                                        {{ $story->product->name }}
                                    </div>
                                @endif
                                <div class="mt-4 flex items-center gap-3">
                                    <div class="flex-1 rounded-full border border-white/60 px-4 py-2 text-sm text-white/75">{{ __('Responder…') }}</div>
                                    <flux:icon.heart class="size-7" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            @empty
                <x-states.empty :title="__('No tienes estados activos')" :description="__('Publica uno para que aparezca en el carrusel del feed durante 24 horas.')" />
            @endforelse
        </div>
    </section>

    <flux:modal name="create-story" class="w-full !max-w-6xl">
        <div class="mb-5">
            <flux:heading size="lg">{{ __('Crear nuevo estado') }}</flux:heading>
            <flux:subheading>{{ __('Comparte novedades, promociones o contenido del día. Durará 24 horas.') }}</flux:subheading>
        </div>

        <form wire:submit="save" class="grid overflow-hidden rounded-2xl border border-zinc-200 lg:grid-cols-2 dark:border-zinc-700">
            <div class="space-y-4 p-5 lg:border-e lg:border-zinc-200 dark:lg:border-zinc-700">
                <div class="flex items-center gap-3">
                    <span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">1</span>
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Contenido del estado') }}</h2>
                </div>

                <flux:select wire:model.live="type" :label="__('Tipo')">
                    <flux:select.option value="imagen">{{ __('Imagen') }}</flux:select.option>
                    <flux:select.option value="promocion">{{ __('Promoción') }}</flux:select.option>
                    <flux:select.option value="producto">{{ __('Producto') }}</flux:select.option>
                    <flux:select.option value="servicio">{{ __('Servicio') }}</flux:select.option>
                </flux:select>

                <x-forms.image-upload-field
                    wire:model="photo"
                    accept="image/*"
                    :title="__('Imagen')"
                    :preview-url="$photo?->temporaryUrl()"
                    :preview-alt="__('Vista previa del estado')"
                    remove-action="removePhoto"
                    :error="$errors->first('photo')"
                />

                <div>
                    <div class="flex items-end gap-2">
                        <flux:input wire:model.live.debounce.300ms="caption" :label="__('Texto (opcional)')" maxlength="280" placeholder="{{ __('Ej: ¡Hoy 20% de descuento!') }}" class="flex-1" />
                        <flux:button type="button" size="sm" variant="ghost" icon="sparkles" wire:click="suggestWithAi" wire:loading.attr="disabled" class="mb-0.5 shrink-0">
                            {{ __('Sugerir con IA') }}
                        </flux:button>
                    </div>
                    @error('caption')
                        <flux:text class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>

                @if ($this->availableProducts->isNotEmpty())
                    <flux:select wire:model.live="product_id" :label="__('Vincular producto (opcional)')">
                        <flux:select.option value="">{{ __('Ninguno') }}</flux:select.option>
                        @foreach ($this->availableProducts as $product)
                            <flux:select.option value="{{ $product->id }}">{{ $product->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Asocia un producto de tu vitrina para que los usuarios puedan verlo.') }}</p>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled">
                        {{ __('Publicar estado') }}
                    </flux:button>
                </div>
            </div>

            @php
                $selectedProduct = $product_id ? $this->availableProducts->firstWhere('id', (int) $product_id) : null;
            @endphp
            <div class="bg-zinc-50 p-5 dark:bg-zinc-900/70">
                <div class="mb-4 flex items-center gap-3">
                    <span class="flex size-7 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-600">2</span>
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Vista previa') }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Así se verá tu estado en la plataforma.') }}</p>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-[310px] rounded-[2.5rem] bg-zinc-950 p-2.5 shadow-2xl ring-1 ring-zinc-900/20">
                    <div class="relative aspect-[9/16] overflow-hidden rounded-[2rem] bg-zinc-800 text-white">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="absolute inset-0 size-full object-cover" alt="{{ __('Vista previa del estado') }}">
                        @else
                            <div class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-gradient-to-br from-zinc-700 to-zinc-900 px-6 text-center text-white/65">
                                <flux:icon.photo class="size-12" />
                                <p class="text-sm">{{ __('Selecciona una imagen para ver la vista previa.') }}</p>
                            </div>
                        @endif

                        <div class="absolute inset-x-0 top-0 bg-gradient-to-b from-black/75 to-transparent px-3 pb-10 pt-3">
                            <div class="mb-3 h-1 rounded-full bg-white/90"></div>
                            <div class="flex items-center gap-2">
                                <div class="flex size-8 items-center justify-center rounded-full bg-white text-xs font-bold text-brand-600">M</div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold">{{ $this->business->name }}</p>
                                    <p class="text-[10px] text-white/75">{{ __('hace un momento') }}</p>
                                </div>
                                <flux:icon.x-mark class="size-5" />
                            </div>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 via-black/45 to-transparent px-4 pb-4 pt-20">
                            <p class="min-h-5 text-sm font-medium">{{ $caption ?: __('Tu texto aparecerá aquí…') }}</p>
                            @if ($selectedProduct)
                                <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-zinc-900">
                                    <flux:icon.cube class="size-3" /> {{ $selectedProduct->name }}
                                </span>
                            @endif
                            <div class="mt-3 flex items-center gap-2">
                                <div class="flex-1 rounded-full border border-white/70 px-3 py-1.5 text-xs text-white/75">{{ __('Responder…') }}</div>
                                <flux:icon.heart class="size-6" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </flux:modal>
</section>
