<?php

use App\Domain\Billing\Exceptions\PlanLimitException;
use App\Domain\Businesses\Actions\SyncBusinessMunicipalities;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Actions\UpdateStorefront;
use App\Domain\Storefronts\Exceptions\IncompleteStorefrontException;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * "Mi Merkamigo en cinco minutos" (E02, 1.2 del TODO). Cinco pasos con
 * autoguardado en cada uno (reutiliza CreateStorefront/UpdateStorefront,
 * sin duplicar reglas con el editor del panel ni la API). Entrada por
 * audio y texto asistido por IA quedan diferidos — ver
 * docs/architecture/decisiones.md.
 */
new #[Title('Crea tu vitrina')] class extends Component
{
    use WithFileUploads;

    public int $step = 1;

    public ?Business $business = null;

    // Paso 1
    public string $name = '';

    public ?string $whatsapp_number = '';

    public ?int $municipality_id = null;

    /** @var array<int, int> */
    public array $additional_municipality_ids = [];

    public ?int $category_id = null;

    public ?string $zone = '';

    // Paso 2
    public ?string $description = '';

    // Paso 3
    public $logo;

    public ?string $logoAlt = '';

    public $cover;

    public ?string $coverAlt = '';

    // Paso 4
    public string $product_name = '';

    public string $product_type = 'producto';

    public ?string $product_description = '';

    public ?float $product_price = null;

    public string $product_price_type = 'exacto';

    public ?string $product_unit = '';

    public bool $product_is_available = true;

    /** @var array<int, string> */
    public array $missing = [];

    public function mount(): void
    {
        $business = Auth::user()->businesses()->where('businesses.status', 'borrador')->latest('businesses.created_at')->first();

        if ($business) {
            $this->business = $business;
            $this->name = $business->name;
            $this->whatsapp_number = $business->whatsapp_number;
            $this->municipality_id = $business->municipality_id;
            $this->additional_municipality_ids = $business->municipalities->pluck('id')->all();
            $this->category_id = $business->category_id;
            $this->zone = $business->zone;
            $this->description = $business->storefront?->description;
            $this->logoAlt = $business->logo_alt_text;
            $this->coverAlt = $business->storefront?->cover_alt_text;

            return;
        }

        // Pedido del usuario: el asistente del panel de emprendedor puede
        // armar el paso 1 a partir de una conversación y traer al
        // negocio aquí ya con los datos listos para revisar — solo aplica
        // sin un borrador en curso, para no pisarlo.
        $this->name = (string) request()->query('nombre', $this->name);
        $this->whatsapp_number = request()->query('whatsapp', $this->whatsapp_number);
        $this->description = (string) request()->query('descripcion', $this->description);

        $categorySlug = request()->query('categoria');
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->where('is_active', true)->first();
            $this->category_id = $category?->id ?? $this->category_id;
        }

        $municipalitySlug = request()->query('municipio');
        if ($municipalitySlug) {
            $municipality = Municipality::where('slug', $municipalitySlug)->where('is_active', true)->first();
            $this->municipality_id = $municipality?->id ?? $this->municipality_id;
        }
    }

    public function goToStep2(): void
    {
        $this->normalizeAdditionalMunicipalities();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'zone' => ['nullable', 'string', 'max:255'],
        ]);

        $additionalMunicipalityIds = $this->validate([
            'additional_municipality_ids' => ['array', 'max:3'],
            'additional_municipality_ids.*' => [
                'integer',
                'exists:municipalities,id',
                Rule::notIn(array_filter([(int) $this->municipality_id])),
            ],
        ])['additional_municipality_ids'];

        if (! $this->business) {
            try {
                $storefront = app(CreateStorefront::class)->handle(Auth::user(), $data);
            } catch (PlanLimitException $e) {
                Flux::toast(variant: 'danger', text: $e->getMessage());

                return;
            }

            $this->business = $storefront->business;
        } else {
            app(UpdateStorefront::class)->handle($this->business, $data, Auth::user());
        }

        app(SyncBusinessMunicipalities::class)->handle($this->business, $additionalMunicipalityIds);

        $this->step = 2;
    }

    public function updatedMunicipalityId(): void
    {
        $this->normalizeAdditionalMunicipalities();
    }

    public function updatedAdditionalMunicipalityIds(): void
    {
        $this->normalizeAdditionalMunicipalities(notify: true);
    }

    private function normalizeAdditionalMunicipalities(bool $notify = false): void
    {
        $selectedMainMunicipalityId = (int) $this->municipality_id;
        $normalized = collect($this->additional_municipality_ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->reject(fn (int $id) => $id === $selectedMainMunicipalityId)
            ->values();

        $trimmed = $normalized->take(3)->all();

        if ($notify && $normalized->count() > 3) {
            Flux::toast(variant: 'warning', text: __('Solo puedes seleccionar hasta 3 municipios adicionales.'));
        }

        $this->additional_municipality_ids = $trimmed;
    }

    public function goToStep3(): void
    {
        $data = $this->validate(['description' => ['nullable', 'string']]);

        app(UpdateStorefront::class)->handle($this->business, $data, Auth::user());

        $this->step = 3;
    }

    public function goToStep4(): void
    {
        $data = array_filter([
            'logo' => $this->logo,
            'logo_alt_text' => $this->logoAlt,
            'cover' => $this->cover,
            'cover_alt_text' => $this->coverAlt,
        ]);

        if ($data !== []) {
            app(UpdateStorefront::class)->handle($this->business, $data, Auth::user());
            $this->reset(['logo', 'cover']);
            $this->business->refresh();
        }

        $this->step = 4;
    }

    public function goToStep5(): void
    {
        if ($this->business->products()->count() === 0 || filled($this->product_name)) {
            $data = $this->validate([
                'product_name' => ['required', 'string', 'max:255'],
                'product_type' => ['required', 'in:producto,servicio'],
                'product_description' => ['nullable', 'string'],
                'product_price' => ['nullable', 'numeric', 'min:0'],
                'product_price_type' => ['required', 'in:exacto,desde,consultar,sin_precio'],
                'product_unit' => ['nullable', 'string', 'max:100'],
                'product_is_available' => ['boolean'],
            ]);

            app(CreateProduct::class)->handle($this->business, [
                'name' => $data['product_name'],
                'type' => $data['product_type'],
                'description' => $data['product_description'],
                'price' => $data['product_price'],
                'price_type' => $data['product_price_type'],
                'unit' => $data['product_unit'],
                'is_available' => $data['product_is_available'],
            ], [], Auth::user());

            $this->business->refresh();
            $this->reset([
                'product_name',
                'product_description',
                'product_price',
                'product_unit',
            ]);
            $this->product_type = 'producto';
            $this->product_price_type = 'exacto';
            $this->product_is_available = true;
        }

        $this->step = 5;
    }

    public function goToStep6(): void
    {
        $this->step = 6;
    }

    public function editInformation(): void
    {
        $this->step = 1;
    }

    public function back(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function publish(): void
    {
        try {
            app(PublishStorefront::class)->handle($this->business, Auth::user());

            Flux::toast(variant: 'success', text: __('¡Tu vitrina fue publicada!'));

            $this->redirectRoute('emprendedores.home', navigate: true);
        } catch (IncompleteStorefrontException $e) {
            $this->missing = $e->missing;
        }
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

    #[Computed]
    public function wizardProducts()
    {
        return $this->business?->products()->latest()->get() ?? collect();
    }
}; ?>

<section class="mx-auto w-full max-w-2xl">
    <flux:heading size="xl">{{ __('Mi Merkamigo en cinco minutos') }}</flux:heading>
    <flux:subheading class="mb-6">
        {{ __('Publica tu vitrina en borrador y complétala a tu ritmo. Puedes salir y volver: tu progreso queda guardado.') }}
    </flux:subheading>

    @php
        $stepLabels = [
            1 => __('Información'),
            2 => __('Tu negocio'),
            3 => __('Fotografías'),
            4 => __('Primer producto'),
            5 => __('Vista previa'),
            6 => __('¡Listo!'),
        ];
    @endphp

    {{--
        Pedido del usuario: que el asistente sepa en qué paso del asistente
        está la persona (ej. "Información básica") para poder ayudarla con
        ese paso puntual. wire:key cambia con $step, así que Livewire trata
        este nodo como nuevo en cada paso y x-init se vuelve a ejecutar —
        el evento sube por el DOM hasta el widget del chat, en otra parte
        de la página, que lo escucha con `.window`.
    --}}
    <div wire:key="wizard-step-marker-{{ $step }}" x-data x-init="$dispatch('wizard-step-changed', { label: @js($stepLabels[$step] ?? null) })"></div>

    <div class="mb-8 flex items-start">
        @foreach ($stepLabels as $i => $label)
            <div class="flex flex-1 flex-col items-center">
                <div class="flex w-full items-center">
                    <div class="h-0.5 flex-1 {{ $i > 1 ? ($step >= $i ? 'bg-brand-600' : 'bg-zinc-200 dark:bg-zinc-700') : 'bg-transparent' }}"></div>
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold {{ $step > $i ? 'bg-brand-600 text-white' : ($step === $i ? 'border-2 border-brand-600 text-brand-600' : 'border-2 border-zinc-200 text-zinc-400 dark:border-zinc-700') }}">
                        @if ($step > $i)
                            <flux:icon.check class="size-4" variant="outline" />
                        @else
                            {{ $i }}
                        @endif
                    </div>
                    <div class="h-0.5 flex-1 {{ $i < 6 ? ($step > $i ? 'bg-brand-600' : 'bg-zinc-200 dark:bg-zinc-700') : 'bg-transparent' }}"></div>
                </div>
                <flux:text class="mt-1.5 text-center text-xs {{ $step === $i ? 'font-medium text-brand-600' : 'text-zinc-400' }}">
                    {{ $label }}
                </flux:text>
            </div>
        @endforeach
    </div>

    @if ($step === 1)
        <form wire:submit="goToStep2" class="space-y-6">
            <flux:heading size="lg">{{ __('1. Información básica') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Nombre del negocio')" required autofocus />
            <flux:input wire:model="whatsapp_number" :label="__('WhatsApp')" type="tel" placeholder="+57 300 000 0000" />

            <flux:select wire:model.live="municipality_id" :label="__('Municipio')">
                <flux:select.option value="">{{ __('Selecciona un municipio') }}</flux:select.option>
                @foreach ($this->municipalities as $municipality)
                    <flux:select.option value="{{ $municipality->id }}">{{ $municipality->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:checkbox.group wire:model.live="additional_municipality_ids" :label="__('¿También atiendes en otros municipios? (opcional)')">
                <flux:text class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Máximo 3 municipios adicionales. El municipio principal no se puede repetir.') }}
                </flux:text>
                <div class="grid gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->municipalities as $municipality)
                        @php
                            $isCurrentMunicipality = (int) $municipality->id === (int) $municipality_id;
                            $isChecked = in_array((int) $municipality->id, array_map('intval', $additional_municipality_ids), true);
                            $hasReachedLimit = count($additional_municipality_ids) >= 3;
                        @endphp

                        <flux:checkbox
                            value="{{ $municipality->id }}"
                            :label="$municipality->name"
                            :disabled="$isCurrentMunicipality || ($hasReachedLimit && ! $isChecked)"
                        />
                    @endforeach
                </div>
            </flux:checkbox.group>

            <flux:select wire:model="category_id" :label="__('Categoría')">
                <flux:select.option value="">{{ __('Selecciona una categoría') }}</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="zone" :label="__('Zona o barrio (opcional)')" />

            <flux:button type="submit" variant="primary" class="w-full">{{ __('Siguiente') }}</flux:button>
        </form>
    @elseif ($step === 2)
        <form wire:submit="goToStep3" class="space-y-6">
            <flux:heading size="lg">{{ __('2. Cuéntanos sobre tu negocio') }}</flux:heading>
            <flux:subheading>{{ __('Por ahora puedes escribirlo; muy pronto podrás grabar un audio y lo transcribimos por ti.') }}</flux:subheading>

            <flux:textarea wire:model="description" :label="__('Descripción')" rows="5" placeholder="{{ __('¿Qué vendes? ¿Qué te hace especial?') }}" />

            <div class="flex gap-3">
                <flux:button variant="ghost" wire:click="back">{{ __('Atrás') }}</flux:button>
                <flux:button type="submit" variant="primary" class="flex-1">{{ __('Siguiente') }}</flux:button>
            </div>
        </form>
    @elseif ($step === 3)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('3. Agrega fotografías') }}</flux:heading>

            <div>
                <x-forms.image-upload-field
                    wire:model="logo"
                    accept="image/*"
                    :title="__('Logo o foto principal')"
                    :preview-url="$business?->logo_path && ! $logo ? $business->logoUrl() : null"
                    :preview-alt="$business?->logo_alt_text ?? $business?->name"
                    :preview-class="'size-20 rounded-xl object-cover'"
                    :error="$errors->first('logo')"
                />
                <flux:input wire:model="logoAlt" class="mt-2" :label="__('Texto alternativo del logo (opcional)')" placeholder="{{ __('Ej: Logo de mi negocio') }}" />
            </div>

            <div>
                <x-forms.image-upload-field
                    wire:model="cover"
                    accept="image/*"
                    :title="__('Portada de tu vitrina')"
                    :preview-url="$business?->storefront?->cover_path && ! $cover ? $business->storefront->coverUrl() : null"
                    :preview-alt="$business?->storefront?->cover_alt_text ?? __('Portada actual')"
                    :error="$errors->first('cover')"
                />
                <flux:input wire:model="coverAlt" class="mt-2" :label="__('Texto alternativo de la portada (opcional)')" placeholder="{{ __('Ej: Fachada de mi negocio') }}" />
            </div>

            <div class="flex gap-3">
                <flux:button variant="ghost" wire:click="back">{{ __('Atrás') }}</flux:button>
                <flux:button variant="primary" class="flex-1" wire:click="goToStep4">{{ __('Siguiente') }}</flux:button>
            </div>
        </div>
    @elseif ($step === 4)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('4. Agrega tu primer producto o servicio') }}</flux:heading>
            <flux:subheading>{{ __('Este paso desbloquea la publicación de la vitrina. Si ya tienes uno creado, puedes continuar sin llenar el formulario otra vez.') }}</flux:subheading>

            @if ($this->wizardProducts->isNotEmpty())
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <flux:text class="font-medium text-zinc-800">{{ __('Ya agregaste estos productos o servicios') }}</flux:text>
                    <div class="mt-3 space-y-2">
                        @foreach ($this->wizardProducts as $product)
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-zinc-50 px-3 py-2">
                                <div>
                                    <div class="font-medium text-zinc-800">{{ $product->name }}</div>
                                    <div class="text-sm text-zinc-500">{{ $product->type === 'producto' ? __('Producto') : __('Servicio') }}</div>
                                </div>
                                <flux:badge size="sm" :color="$product->status === 'publicado' ? 'green' : 'zinc'">{{ ucfirst($product->status) }}</flux:badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="product_type" :label="__('Tipo')">
                    <flux:select.option value="producto">{{ __('Producto') }}</flux:select.option>
                    <flux:select.option value="servicio">{{ __('Servicio') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model="product_price_type" :label="__('Precio')">
                    <flux:select.option value="exacto">{{ __('Precio exacto') }}</flux:select.option>
                    <flux:select.option value="desde">{{ __('Desde') }}</flux:select.option>
                    <flux:select.option value="consultar">{{ __('Consultar') }}</flux:select.option>
                    <flux:select.option value="sin_precio">{{ __('Sin precio') }}</flux:select.option>
                </flux:select>
            </div>

            <flux:input wire:model="product_name" :label="__('Nombre del producto o servicio')" placeholder="{{ __('Ej: Desayuno especial, Corte de cabello, Domicilio express') }}" />
            <flux:textarea wire:model="product_description" :label="__('Descripción breve (opcional)')" rows="3" placeholder="{{ __('Cuéntale al cliente qué incluye o cómo funciona.') }}" />

            @if (in_array($product_price_type, ['exacto', 'desde']))
                <flux:input wire:model="product_price" :label="__('Valor')" type="number" step="0.01" min="0" />
            @endif

            <flux:input wire:model="product_unit" :label="__('Unidad (opcional)')" placeholder="{{ __('Ej: unidad, hora, porción') }}" />
            <flux:checkbox wire:model="product_is_available" :label="__('Disponible')" />

            <div class="flex gap-3">
                <flux:button variant="ghost" wire:click="back">{{ __('Editar información') }}</flux:button>
                <flux:button variant="primary" class="flex-1" wire:click="goToStep5">{{ __('Siguiente') }}</flux:button>
            </div>
        </div>
    @elseif ($step === 5)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('5. Revisa la vista previa') }}</flux:heading>

            <x-storefront-preview :business="$business" />

            <div class="flex gap-3">
                <flux:button variant="ghost" wire:click="back">{{ __('Editar producto') }}</flux:button>
                <flux:button variant="primary" class="flex-1" wire:click="goToStep6">{{ __('Revisar y publicar') }}</flux:button>
            </div>
        </div>
    @else
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('6. Publicación') }}</flux:heading>

            @if ($missing !== [])
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
                    <flux:text class="font-medium">{{ __('Te falta completar:') }}</flux:text>
                    <ul class="mt-2 list-inside list-disc text-sm">
                        @foreach ($missing as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>

                    @if (in_array('Al menos un producto o servicio', $missing, true))
                        <flux:button
                            variant="ghost"
                            size="sm"
                            icon="plus"
                            class="mt-3"
                            wire:click="$set('step', 4)"
                        >
                            {{ __('Agregar producto o servicio') }}
                        </flux:button>
                    @endif

                    <flux:button
                        variant="ghost"
                        size="sm"
                        icon="lifebuoy"
                        class="mt-3"
                        :href="route('soporte')"
                        wire:navigate
                    >
                        {{ __('Ayúdame a terminar mi vitrina') }}
                    </flux:button>
                </div>
            @endif

            <div class="flex gap-3">
                <flux:button variant="ghost" wire:click="editInformation">{{ __('Editar información') }}</flux:button>
                <flux:button variant="primary" class="flex-1" wire:click="publish">{{ __('Revisar y publicar') }}</flux:button>
            </div>
        </div>
    @endif
</section>
