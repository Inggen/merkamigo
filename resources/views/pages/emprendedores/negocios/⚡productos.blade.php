<?php

use App\Domain\Billing\Exceptions\PlanLimitException;
use App\Domain\Billing\Models\Plan;
use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\DuplicateProduct;
use App\Domain\Storefronts\Actions\GenerateProductDescription;
use App\Domain\Storefronts\Actions\GenerateProductImage;
use App\Domain\Storefronts\Actions\MoveProductToBusiness;
use App\Domain\Storefronts\Actions\UpdateProduct;
use App\Domain\Storefronts\Actions\UpdateProductFile;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Storefronts\Models\ProductMedia;
use App\Domain\Subscriptions\Actions\CreateSubscriptionPlan;
use App\Support\Ai\AiImagePrompt;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Gestión de productos y servicios (E05, 1.4 del TODO): pestañas
 * Todos/Productos/Servicios, crear/editar en drawer sin abandonar el
 * listado, reordenar, duplicar, variantes y precio promocional.
 */
new #[Title('Productos y servicios')] class extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public int $selectedBusinessId;

    public int $productBusinessId;

    public string $filter = 'todos';

    public ?int $editingId = null;

    public string $name = '';

    public string $type = 'producto';

    public ?string $description = '';

    public ?float $price = null;

    public string $price_type = 'exacto';

    public ?string $unit = '';

    public bool $is_available = true;

    public bool $has_promo = false;

    public ?float $promo_price = null;

    public ?string $promo_label = '';

    public ?string $promo_starts_at = null;

    public ?string $promo_ends_at = null;

    public ?string $gtin = '';

    public ?string $mpn = '';

    public ?string $brand = '';

    public string $condition = 'nuevo';

    public string $sale_type = 'unica';

    public string $subscription_frequency = 'mensual';

    public ?int $subscription_trial_days = null;

    public ?string $subscription_benefits = '';

    public bool $subscription_is_active = true;

    public $digitalFile = null;

    /** @var array{id: int, name: string}|null */
    public ?array $existingFile = null;

    /** @var array<int, array{label: string, price: ?float}> */
    public array $variants = [];

    /** @var array<int, mixed> */
    public array $photos = [];

    /** @var array<int, array{id: int, url: string}> */
    public array $existingMedia = [];

    /** @var array<int, int> */
    public array $removeMediaIds = [];

    /** @var array<int, string> media id => texto alternativo */
    public array $photoAlts = [];

    public string $productImageStyle = AiImagePrompt::ULTRAREALISTA;

    /**
     * El middleware `business.team` solo corre en la carga inicial de la
     * página: las peticiones AJAX de Livewire (crear/editar/archivar
     * producto...) van al endpoint genérico `/livewire/update`, que no pasa
     * por esa ruta ni por ese middleware. `boot()` sí se ejecuta en cada
     * petición (inicial y subsecuentes), así que es el único lugar donde
     * fijar el team de forma confiable en todo el ciclo de vida del
     * componente — sin esto, cualquier acción después del primer render
     * pierde el contexto de equipo y falla con 403.
     */
    public function boot(): void
    {
        if (isset($this->selectedBusinessId)) {
            setPermissionsTeamId($this->selectedBusinessId);
            Auth::user()?->unsetRelation('roles');
        } elseif (isset($this->businessId)) {
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
        $this->selectedBusinessId = $business->id;
        $this->productBusinessId = $business->id;
    }

    #[Computed]
    public function business(): Business
    {
        return Business::findOrFail($this->businessId);
    }

    #[Computed]
    public function availableBusinesses()
    {
        return Auth::user()
            ->businesses()
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function products()
    {
        return $this->business->products()
            ->when($this->filter !== 'todos', fn ($q) => $q->where('type', $this->filter === 'productos' ? 'producto' : 'servicio'))
            ->with('media')
            ->get();
    }

    public function openCreate(): void
    {
        $this->authorize('update', $this->business);

        $this->reset(['editingId', 'name', 'description', 'price', 'unit', 'photos', 'has_promo', 'promo_price', 'promo_label', 'promo_starts_at', 'promo_ends_at', 'variants', 'existingMedia', 'removeMediaIds', 'photoAlts', 'gtin', 'mpn', 'brand', 'digitalFile', 'existingFile', 'subscription_trial_days', 'subscription_benefits']);
        $this->type = 'producto';
        $this->price_type = 'exacto';
        $this->is_available = true;
        $this->condition = 'nuevo';
        $this->sale_type = 'unica';
        $this->subscription_frequency = 'mensual';
        $this->subscription_is_active = true;
        $this->productBusinessId = $this->selectedBusinessId;
        $this->resetValidation();
    }

    public function openEdit(int $productId): void
    {
        $this->authorize('update', $this->business);

        $product = $this->business->products()->with(['variants', 'media', 'files', 'subscriptionPlan'])->findOrFail($productId);

        $this->editingId = $product->id;
        $this->productBusinessId = $product->business_id;
        $this->name = $product->name;
        $this->type = $product->type;
        $this->sale_type = $product->sale_type;
        $this->digitalFile = null;
        $this->existingFile = $product->files->first()
            ? ['id' => $product->files->first()->id, 'name' => $product->files->first()->original_name]
            : null;

        if ($plan = $product->subscriptionPlan) {
            $this->subscription_frequency = $plan->frequency;
            $this->subscription_trial_days = $plan->trial_days;
            $this->subscription_benefits = $plan->benefits;
            $this->subscription_is_active = $plan->is_active;
        } else {
            $this->subscription_frequency = 'mensual';
            $this->subscription_trial_days = null;
            $this->subscription_benefits = null;
            $this->subscription_is_active = true;
        }

        $this->description = $product->description;
        $this->price = $product->price ? (float) $product->price : null;
        $this->price_type = $product->price_type;
        $this->unit = $product->unit;
        $this->is_available = $product->is_available;
        $this->photos = [];
        $this->existingMedia = $product->media->map(fn ($media) => ['id' => $media->id, 'url' => $media->url()])->all();
        $this->removeMediaIds = [];
        $this->photoAlts = $product->media->pluck('alt_text', 'id')->all();

        $this->has_promo = (bool) $product->promo_price;
        $this->promo_price = $product->promo_price ? (float) $product->promo_price : null;
        $this->promo_label = $product->promo_label;
        $this->promo_starts_at = $product->promo_starts_at?->format('Y-m-d');
        $this->promo_ends_at = $product->promo_ends_at?->format('Y-m-d');

        $this->gtin = $product->gtin;
        $this->mpn = $product->mpn;
        $this->brand = $product->brand;
        $this->condition = $product->condition;

        $this->variants = $product->variants->map(fn ($variant) => [
            'label' => $variant->label,
            'price' => $variant->price ? (float) $variant->price : null,
        ])->all();

        $this->resetValidation();
    }

    /**
     * Mismo criterio que el editor de vitrina: mejorar descripción y
     * generar fotos con IA es un beneficio del plan Emprendedor (pedido
     * del usuario) — el superadmin siempre puede usarlas, incluso
     * entrando "como" el dueño del negocio (`User::canBypassPlanGates()`).
     */
    public function canUseAiForProducts(): bool
    {
        return $this->business->activePlan()->slug === Plan::EMPRENDEDOR
            || (Auth::user()?->canBypassPlanGates() ?? false);
    }

    /**
     * Genera (o mejora) la descripción del producto/servicio con IA a
     * partir de los demás campos ya llenos del formulario (pedido del
     * usuario). Funciona tanto creando uno nuevo como editando uno
     * existente — el resultado queda editable, no se guarda solo.
     */
    public function improveProductDescription(): void
    {
        $this->authorize('update', $this->business);

        if (! $this->canUseAiForProducts()) {
            Flux::toast(variant: 'warning', text: __('Mejorar descripción con IA es un beneficio del plan Emprendedor.'));

            return;
        }

        if (trim($this->name) === '') {
            Flux::toast(variant: 'warning', text: __('Escribe primero el nombre del producto o servicio.'));

            return;
        }

        $generated = app(GenerateProductDescription::class)->handle($this->business, [
            'name' => $this->name,
            'type' => $this->type,
            'price' => $this->price,
            'price_type' => $this->price_type,
            'unit' => $this->unit,
            'description' => $this->description,
        ]);

        if ($generated === null || $generated === '') {
            Flux::toast(variant: 'danger', text: __('No pudimos generar la descripción. Intenta de nuevo en un momento.'));

            return;
        }

        $this->description = $generated;
    }

    /**
     * Genera una foto con IA para el producto/servicio que se está
     * editando (pedido del usuario) — solo disponible editando uno ya
     * guardado, porque se adjunta directo como una foto real
     * (`UpdateProduct`), igual que si se hubiera subido a mano.
     */
    public function generateProductPhoto(): void
    {
        $this->authorize('update', $this->business);

        if (! $this->canUseAiForProducts()) {
            Flux::toast(variant: 'warning', text: __('Generar fotos con IA es un beneficio del plan Emprendedor.'));

            return;
        }

        if (! $this->editingId) {
            Flux::toast(variant: 'warning', text: __('Guarda primero el producto para poder generarle una foto con IA.'));

            return;
        }

        $product = $this->business->products()->with('media')->findOrFail($this->editingId);
        $style = AiImagePrompt::isValidStyle($this->productImageStyle) ? $this->productImageStyle : AiImagePrompt::ULTRAREALISTA;

        $generatedPhoto = app(GenerateProductImage::class)->handle($product, $style);

        if ($generatedPhoto === null) {
            Flux::toast(variant: 'danger', text: __('No pudimos generar la foto. Intenta de nuevo en un momento.'));

            return;
        }

        try {
            $product = app(UpdateProduct::class)->handle($product, [], [$generatedPhoto], [], Auth::user());
        } catch (PlanLimitException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->existingMedia = $product->media->map(fn ($media) => ['id' => $media->id, 'url' => $media->url()])->all();
        $this->photoAlts = $product->media->pluck('alt_text', 'id')->all();

        Flux::toast(variant: 'success', text: __('Foto generada y agregada al producto.'));
    }

    public function updatedSelectedBusinessId(int|string $businessId): void
    {
        $business = $this->resolveManagedBusiness((int) $businessId);

        $this->redirectRoute('emprendedores.negocios.productos', ['business' => $business], navigate: true);
    }

    public function addVariant(): void
    {
        $this->variants[] = ['label' => '', 'price' => null];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function save(): void
    {
        $targetBusiness = $this->resolveManagedBusiness($this->productBusinessId);

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'sale_type' => $this->sale_type,
            'description' => $this->description,
            'price' => $this->price,
            'price_type' => $this->price_type,
            'unit' => $this->unit,
            'is_available' => $this->is_available,
            'promo_price' => $this->has_promo ? $this->promo_price : null,
            'promo_label' => $this->has_promo ? $this->promo_label : null,
            'promo_starts_at' => $this->has_promo ? $this->promo_starts_at : null,
            'promo_ends_at' => $this->has_promo ? $this->promo_ends_at : null,
            'gtin' => $this->gtin ?: null,
            'mpn' => $this->mpn ?: null,
            'brand' => $this->brand ?: null,
            'condition' => $this->condition,
            'variants' => array_values(array_filter(
                $this->variants,
                fn (array $variant) => trim($variant['label'] ?? '') !== '',
            )),
        ];

        try {
            if ($this->editingId) {
                $product = $this->business->products()->findOrFail($this->editingId);
                $this->authorize('update', $product->business);
                $product = app(UpdateProduct::class)->handle($product, $data, $this->photos, $this->removeMediaIds, Auth::user());

                if ($product->business_id !== $targetBusiness->id) {
                    $product = app(MoveProductToBusiness::class)->handle($product->fresh(), $targetBusiness, Auth::user());
                }
            } else {
                $product = app(CreateProduct::class)->handle($targetBusiness, $data, $this->photos, Auth::user());
            }

            if (! empty($this->photoAlts)) {
                $product->media()
                    ->whereKey(array_keys($this->photoAlts))
                    ->get()
                    ->each(fn (ProductMedia $media) => $media->update([
                        'alt_text' => trim((string) ($this->photoAlts[$media->id] ?? '')) ?: null,
                    ]));
            }

            if (! empty($this->existingMedia)) {
                $orderedIds = array_column($this->existingMedia, 'id');

                foreach ($orderedIds as $index => $mediaId) {
                    ProductMedia::whereKey($mediaId)->update(['position' => $index]);
                }

                $nextPosition = count($orderedIds);
                $product->media()->whereNotIn('id', $orderedIds)->orderBy('position')->get()
                    ->each(function (ProductMedia $media) use (&$nextPosition) {
                        $media->update(['position' => $nextPosition++]);
                    });
            }

            if ($this->sale_type === 'suscripcion') {
                app(CreateSubscriptionPlan::class)->handle($product, [
                    'frequency' => $this->subscription_frequency,
                    'trial_days' => $this->subscription_trial_days,
                    'benefits' => $this->subscription_benefits,
                    'is_active' => $this->subscription_is_active,
                ]);
            }

            if ($this->type === 'digital' && $this->digitalFile) {
                app(UpdateProductFile::class)->handle($product, $this->digitalFile);
            }
        } catch (PlanLimitException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        unset($this->products);

        if ($this->selectedBusinessId !== $targetBusiness->id) {
            $this->redirectRoute('emprendedores.negocios.productos', ['business' => $targetBusiness], navigate: true);

            return;
        }

        Flux::modal('product-form')->close();
        Flux::toast(variant: 'success', text: __('Producto guardado.'));
    }

    public function removeExistingMedia(int $mediaId): void
    {
        $this->authorize('update', $this->business);

        ProductMedia::whereHas('product', fn ($q) => $q->where('business_id', $this->businessId))
            ->findOrFail($mediaId);

        if (! in_array($mediaId, $this->removeMediaIds, true)) {
            $this->removeMediaIds[] = $mediaId;
        }

        $this->existingMedia = array_values(array_filter(
            $this->existingMedia,
            fn (array $item) => $item['id'] !== $mediaId,
        ));

        unset($this->photoAlts[$mediaId]);
    }

    /**
     * Reordena las fotos ya guardadas arrastrándolas (pedido del
     * usuario) — solo cambia el orden en memoria; el orden final se
     * guarda en `position` cuando se presiona "Guardar", igual que el
     * texto alternativo.
     */
    public function reorderExistingMedia(int $draggedId, int $targetId): void
    {
        $this->authorize('update', $this->business);

        if ($draggedId === $targetId) {
            return;
        }

        $ids = array_column($this->existingMedia, 'id');
        $draggedIndex = array_search($draggedId, $ids, true);
        $targetIndex = array_search($targetId, $ids, true);

        if ($draggedIndex === false || $targetIndex === false) {
            return;
        }

        $item = $this->existingMedia[$draggedIndex];
        array_splice($this->existingMedia, $draggedIndex, 1);
        array_splice($this->existingMedia, $targetIndex, 0, [$item]);
    }

    public function removePendingPhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function duplicate(int $productId): void
    {
        $this->authorize('update', $this->business);

        $product = $this->business->products()->findOrFail($productId);
        app(DuplicateProduct::class)->handle($product, Auth::user());

        unset($this->products);
        Flux::toast(variant: 'success', text: __('Producto duplicado como borrador.'));
    }

    public function archive(int $productId): void
    {
        $this->authorize('update', $this->business);

        $product = $this->business->products()->findOrFail($productId);
        app(UpdateProduct::class)->handle($product, ['status' => 'archivado'], [], [], Auth::user());

        unset($this->products);
        Flux::toast(text: __('Producto archivado.'));
    }

    public function publish(int $productId): void
    {
        $this->authorize('update', $this->business);

        $product = $this->business->products()->findOrFail($productId);
        app(UpdateProduct::class)->handle($product, ['status' => 'publicado'], [], [], Auth::user());

        unset($this->products);
    }

    public function move(int $productId, int $direction): void
    {
        $this->authorize('update', $this->business);

        $products = $this->business->products()->orderBy('position')->get();
        $index = $products->search(fn (Product $p) => $p->id === $productId);

        $swapIndex = $index + $direction;

        if ($index === false || ! $products->has($swapIndex)) {
            return;
        }

        $current = $products[$index];
        $swap = $products[$swapIndex];

        [$current->position, $swap->position] = [$swap->position, $current->position];
        $current->save();
        $swap->save();

        unset($this->products);
    }

    private function resolveManagedBusiness(int $businessId): Business
    {
        $business = Auth::user()
            ->businesses()
            ->where('businesses.id', $businessId)
            ->firstOrFail();

        setPermissionsTeamId($business->id);
        Auth::user()->unsetRelation('roles');
        $this->authorize('update', $business);

        return $business;
    }
}; ?>

<section class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div class="space-y-3">
            <div class="flex items-center gap-1.5">
                <flux:heading size="xl">{{ __('Productos y servicios') }}</flux:heading>
                <flux:tooltip :content="__('Solo lo publicado se ve en tu vitrina. Archiva lo que ya no ofreces en vez de borrarlo, para no perder su historial.')">
                    <flux:icon.question-mark-circle class="size-4 shrink-0 text-zinc-400" variant="outline" />
                </flux:tooltip>
            </div>

            @if ($this->availableBusinesses->count() > 1)
                <div class="max-w-xs">
                    <flux:select wire:model.live="selectedBusinessId" :label="__('Vitrina')">
                        @foreach ($this->availableBusinesses as $availableBusiness)
                            <flux:select.option value="{{ $availableBusiness->id }}">{{ $availableBusiness->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            @endif
        </div>

        <flux:modal.trigger name="product-form">
            <flux:button variant="primary" wire:click="openCreate">{{ __('Agregar') }}</flux:button>
        </flux:modal.trigger>
    </div>

    <div class="flex gap-2">
        <flux:button size="sm" :variant="$filter === 'todos' ? 'primary' : 'ghost'" wire:click="$set('filter', 'todos')">{{ __('Todos') }}</flux:button>
        <flux:button size="sm" :variant="$filter === 'productos' ? 'primary' : 'ghost'" wire:click="$set('filter', 'productos')">{{ __('Productos') }}</flux:button>
        <flux:button size="sm" :variant="$filter === 'servicios' ? 'primary' : 'ghost'" wire:click="$set('filter', 'servicios')">{{ __('Servicios') }}</flux:button>
    </div>

    @if ($this->products->isEmpty())
        <x-states.empty
            title="{{ __('Todavía no tienes productos') }}"
            description="{{ __('Agrega tu primer producto o servicio para poder publicar tu vitrina.') }}"
        />
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->products as $product)
                <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="relative aspect-square bg-zinc-100 dark:bg-zinc-800">
                        @if ($product->media->isNotEmpty())
                            <img src="{{ $product->media->first()->url() }}" class="h-full w-full object-cover" alt="{{ $product->media->first()->alt_text ?? $product->name }}">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <flux:icon.photo class="size-8 text-zinc-300 dark:text-zinc-600" variant="outline" />
                            </div>
                        @endif

                        <div class="absolute top-2 right-2 flex flex-col items-end gap-1">
                            <flux:badge size="sm" :color="$product->status === 'publicado' ? 'green' : 'zinc'">
                                {{ ucfirst($product->status) }}
                            </flux:badge>
                            @if ($product->isSoldOut())
                                <flux:badge size="sm" color="red">{{ __('Agotado') }}</flux:badge>
                            @endif
                            @if ($product->isSubscription())
                                <flux:badge size="sm" color="purple">{{ __('Suscripción') }}</flux:badge>
                            @endif
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="truncate font-medium">{{ $product->name }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $product->type === 'producto' ? __('Producto') : __('Servicio') }}
                            @if ($product->hasActivePromo())
                                · <span class="text-red-600 dark:text-red-400">${{ number_format((float) $product->promo_price, 0, ',', '.') }}</span>
                            @elseif ($product->price)
                                · ${{ number_format((float) $product->price, 0, ',', '.') }}
                            @endif
                        </div>

                        @if ($product->type === 'producto' && $this->business->google_merchant_enabled)
                            @php
                                $googleStatusMeta = match ($product->google_merchant_status) {
                                    'publicado' => ['Publicado en Google', 'green'],
                                    'pendiente' => ['Pendiente en Google', 'blue'],
                                    'sincronizando' => ['Sincronizando con Google', 'blue'],
                                    'requiere_ajustes' => ['Requiere ajustes para Google', 'yellow'],
                                    'error' => ['Error en Google', 'red'],
                                    default => ['No publicado en Google', 'zinc'],
                                };
                            @endphp
                            <flux:badge
                                size="sm"
                                :color="$googleStatusMeta[1]"
                                class="mt-2"
                                :title="\App\Support\GoogleMerchant\GoogleMerchantErrorTranslator::translate($product->google_merchant_last_error)"
                            >
                                {{ $googleStatusMeta[0] }}
                            </flux:badge>
                        @endif

                        <div class="mt-3 flex items-center justify-between border-t border-zinc-100 pt-2 dark:border-zinc-800">
                            <div class="flex items-center">
                                <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="move({{ $product->id }}, -1)" />
                                <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="move({{ $product->id }}, 1)" />
                            </div>

                            <div class="flex items-center gap-1">
                                <flux:modal.trigger name="product-form">
                                    <flux:button size="sm" variant="ghost" wire:click="openEdit({{ $product->id }})">{{ __('Editar') }}</flux:button>
                                </flux:modal.trigger>

                                @if ($product->status !== 'publicado')
                                    <flux:button size="sm" variant="ghost" wire:click="publish({{ $product->id }})">{{ __('Publicar') }}</flux:button>
                                @endif

                                <flux:button size="sm" variant="ghost" icon="document-duplicate" wire:click="duplicate({{ $product->id }})" />

                                @if ($product->status !== 'archivado')
                                    <flux:button size="sm" variant="ghost" icon="archive-box" wire:click="archive({{ $product->id }})" wire:confirm="{{ __('¿Archivar este producto?') }}" />
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="product-form" variant="flyout" class="max-w-md">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Editar producto') : __('Nuevo producto') }}</flux:heading>

            <flux:select wire:model="productBusinessId" :label="__('Vitrina asociada')">
                @foreach ($this->availableBusinesses as $availableBusiness)
                    <flux:select.option value="{{ $availableBusiness->id }}">{{ $availableBusiness->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="type" :label="__('Tipo')">
                <flux:select.option value="producto">{{ __('Producto') }}</flux:select.option>
                <flux:select.option value="servicio">{{ __('Servicio') }}</flux:select.option>
                <flux:select.option value="digital">{{ __('Digital (ebook, curso, plantilla...)') }}</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="sale_type" :label="__('Venta')">
                <flux:select.option value="unica">{{ __('Compra única') }}</flux:select.option>
                <flux:select.option value="suscripcion">{{ __('Suscripción (cobro periódico)') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="name" :label="__('Nombre')" required />
            <div>
                <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                    <flux:text class="font-medium">{{ __('Descripción breve') }}</flux:text>

                    @if ($this->canUseAiForProducts())
                        <flux:button type="button" size="sm" variant="ghost" icon="sparkles" class="text-rose-600! hover:bg-rose-50! dark:text-rose-400! dark:hover:bg-rose-500/10!" wire:click="improveProductDescription" wire:loading.attr="disabled" wire:target="improveProductDescription">
                            <span wire:loading.remove wire:target="improveProductDescription">{{ __('Mejorar descripción') }}</span>
                            <span wire:loading wire:target="improveProductDescription">{{ __('Generando...') }}</span>
                        </flux:button>
                    @endif
                </div>

                <flux:textarea wire:model="description" rows="3" />
            </div>

            <flux:select wire:model="price_type" :label="__('Precio')">
                <flux:select.option value="exacto">{{ __('Precio exacto') }}</flux:select.option>
                <flux:select.option value="desde">{{ __('Desde') }}</flux:select.option>
                <flux:select.option value="consultar">{{ __('Consultar') }}</flux:select.option>
                <flux:select.option value="sin_precio">{{ __('Sin precio') }}</flux:select.option>
            </flux:select>

            @if (in_array($price_type, ['exacto', 'desde']))
                <flux:input wire:model="price" :label="__('Valor')" type="number" step="0.01" min="0" />
            @endif

            <flux:input wire:model="unit" :label="__('Unidad (opcional)')" placeholder="{{ __('Ej: porción, unidad, hora') }}" />

            <flux:checkbox wire:model="is_available" :label="__('Disponible')" />

            <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                <flux:checkbox wire:model.live="has_promo" :label="__('Tiene promoción')" />

                @if ($has_promo)
                    <flux:input wire:model="promo_price" :label="__('Precio promocional')" type="number" step="0.01" min="0" />
                    <flux:input wire:model="promo_label" :label="__('Etiqueta (opcional)')" placeholder="{{ __('Ej: Oferta de la semana') }}" />
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input wire:model="promo_starts_at" :label="__('Desde')" type="date" />
                        <flux:input wire:model="promo_ends_at" :label="__('Hasta')" type="date" />
                    </div>
                @endif
            </div>

            @if ($sale_type === 'suscripcion')
                <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:text class="font-medium">{{ __('Plan de suscripción') }}</flux:text>

                    <flux:select wire:model="subscription_frequency" :label="__('Frecuencia de cobro')">
                        <flux:select.option value="semanal">{{ __('Semanal') }}</flux:select.option>
                        <flux:select.option value="mensual">{{ __('Mensual') }}</flux:select.option>
                        <flux:select.option value="trimestral">{{ __('Trimestral') }}</flux:select.option>
                        <flux:select.option value="anual">{{ __('Anual') }}</flux:select.option>
                    </flux:select>

                    <flux:input wire:model="subscription_trial_days" :label="__('Días de prueba gratis (opcional)')" type="number" min="0" max="365" />

                    <flux:textarea wire:model="subscription_benefits" :label="__('Beneficios (opcional)')" rows="2" placeholder="{{ __('Ej: Acceso a todo el contenido, soporte prioritario') }}" />

                    <flux:checkbox wire:model="subscription_is_active" :label="__('Aceptar nuevos suscriptores')" />
                </div>
            @endif

            @if ($type === 'digital')
                <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:text class="font-medium">{{ __('Archivo entregable') }}</flux:text>
                    <flux:text class="text-sm text-zinc-500">{{ __('Solo lo pueden descargar clientes con acceso vigente (compra o suscripción activa).') }}</flux:text>

                    @if ($existingFile)
                        <flux:text class="text-sm">{{ __('Archivo actual: :name', ['name' => $existingFile['name']]) }}</flux:text>
                    @endif

                    <input type="file" wire:model="digitalFile" class="block w-full text-sm">
                    @error('digitalFile')
                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            @endif

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:text class="font-medium">{{ __('Variantes (opcional)') }}</flux:text>
                    <flux:button type="button" size="sm" variant="ghost" icon="plus" wire:click="addVariant">{{ __('Agregar') }}</flux:button>
                </div>

                @foreach ($variants as $index => $variant)
                    <div class="flex items-end gap-2">
                        <flux:input wire:model="variants.{{ $index }}.label" placeholder="{{ __('Ej: Porción individual') }}" class="flex-1" />
                        <flux:input wire:model="variants.{{ $index }}.price" type="number" step="0.01" min="0" placeholder="{{ __('Precio') }}" class="w-28" />
                        <flux:button type="button" size="sm" variant="ghost" icon="trash" wire:click="removeVariant({{ $index }})" />
                    </div>
                @endforeach
            </div>

            @if ($type === 'producto')
                <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="space-y-1">
                        <flux:text class="font-medium">{{ __('Identificadores para Google Shopping (opcional)') }}</flux:text>
                        <flux:text class="text-sm text-zinc-500">{{ __('Si tu producto es artesanal, personalizado o único, puedes dejar estos campos vacíos.') }}</flux:text>
                    </div>

                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <flux:input wire:model="gtin" :label="__('Código de barras (GTIN)')" placeholder="{{ __('Ej: 7701234567890') }}" />
                        <flux:input wire:model="mpn" :label="__('Referencia del fabricante (MPN)')" />
                        <flux:input wire:model="brand" :label="__('Marca (opcional)')" placeholder="{{ __('Si no la llenas, se usa el nombre de tu negocio') }}" />
                        <flux:select wire:model="condition" :label="__('Condición')">
                            <flux:select.option value="nuevo">{{ __('Nuevo') }}</flux:select.option>
                            <flux:select.option value="usado">{{ __('Usado') }}</flux:select.option>
                            <flux:select.option value="reacondicionado">{{ __('Reacondicionado') }}</flux:select.option>
                        </flux:select>
                    </div>
                </div>
            @endif

            <div>
                <div class="mb-2 flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-1">
                        <flux:text class="font-medium">{{ __('Fotos') }}</flux:text>
                        <flux:text class="text-sm text-zinc-500">{{ __('Agrega una o varias imágenes para mostrar mejor tu producto o servicio.') }}</flux:text>
                    </div>

                    @if ($this->canUseAiForProducts() && $editingId)
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:select wire:model="productImageStyle" size="sm" class="w-40">
                                @foreach (\App\Support\Ai\AiImagePrompt::styles() as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:button type="button" size="sm" variant="ghost" icon="sparkles" class="text-rose-600! hover:bg-rose-50! dark:text-rose-400! dark:hover:bg-rose-500/10!" wire:click="generateProductPhoto" wire:loading.attr="disabled" wire:target="generateProductPhoto">
                                <span wire:loading.remove wire:target="generateProductPhoto">{{ __('Generar con IA') }}</span>
                                <span wire:loading wire:target="generateProductPhoto">{{ __('Generando...') }}</span>
                            </flux:button>
                        </div>
                    @endif
                </div>

                @if (! empty($existingMedia))
                    <div class="mb-3 space-y-2" x-data="{ draggedId: null, overId: null }">
                        @if (count($existingMedia) > 1)
                            <flux:text class="text-xs text-zinc-500">{{ __('Arrastra una foto para cambiar el orden. La primera es la que se ve primero en tu vitrina.') }}</flux:text>
                        @endif

                        @foreach ($existingMedia as $item)
                            <div
                                wire:key="existing-media-{{ $item['id'] }}"
                                draggable="true"
                                x-on:dragstart="draggedId = {{ $item['id'] }}"
                                x-on:dragend="draggedId = null; overId = null"
                                x-on:dragenter.prevent="overId = {{ $item['id'] }}"
                                x-on:dragover.prevent
                                x-on:drop.prevent="$wire.reorderExistingMedia(draggedId, {{ $item['id'] }}); draggedId = null; overId = null"
                                class="flex cursor-move items-center gap-2 rounded-xl p-1 transition"
                                x-bind:class="overId === {{ $item['id'] }} && draggedId !== {{ $item['id'] }} ? 'bg-brand-50 dark:bg-brand-500/10' : ''"
                                x-bind:style="draggedId === {{ $item['id'] }} ? 'opacity: 0.4' : ''"
                            >
                                <flux:icon.arrows-up-down class="size-4 shrink-0 text-zinc-400" variant="outline" />
                                <img src="{{ $item['url'] }}" class="size-12 shrink-0 rounded-lg object-cover" alt="{{ $photoAlts[$item['id']] ?? '' }}">
                                <flux:input wire:model="photoAlts.{{ $item['id'] }}" class="flex-1" placeholder="{{ __('Texto alternativo de esta foto (opcional)') }}" />
                                <flux:button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    icon="trash"
                                    wire:click="removeExistingMedia({{ $item['id'] }})"
                                    aria-label="{{ __('Eliminar esta imagen') }}"
                                />
                            </div>
                        @endforeach
                    </div>
                @endif

                <x-forms.image-upload-field
                    wire:model="photos"
                    multiple
                    accept="image/*"
                    :title="null"
                    :preview-urls="collect($photos)->map(fn ($photo) => $photo->temporaryUrl())->all()"
                    remove-action="removePendingPhoto"
                    :error="$errors->first('photos')"
                />

                <div wire:loading wire:target="photos" class="mt-2">
                    <flux:text class="text-sm text-zinc-500">{{ __('Cargando fotos...') }}</flux:text>
                </div>

            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancelar') }}</flux:button>
                </flux:modal.close>

                <flux:button type="submit" variant="primary">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
