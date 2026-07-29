<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\UpdateProduct;
use App\Domain\Storefronts\Models\Product;
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
 * listado, reordenar.
 */
new #[Title('Productos y servicios')] class extends Component {
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $filter = 'todos';

    public ?int $editingId = null;

    public string $name = '';
    public string $type = 'producto';
    public ?string $description = '';
    public ?float $price = null;
    public string $price_type = 'exacto';
    public ?string $unit = '';
    public bool $is_available = true;

    /** @var array<int, mixed> */
    public array $photos = [];

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

        $this->reset(['editingId', 'name', 'description', 'price', 'unit', 'photos']);
        $this->type = 'producto';
        $this->price_type = 'exacto';
        $this->is_available = true;
        $this->resetValidation();
    }

    public function openEdit(int $productId): void
    {
        $this->authorize('update', $this->business);

        $product = $this->business->products()->findOrFail($productId);

        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->type = $product->type;
        $this->description = $product->description;
        $this->price = $product->price ? (float) $product->price : null;
        $this->price_type = $product->price_type;
        $this->unit = $product->unit;
        $this->is_available = $product->is_available;
        $this->photos = [];
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->authorize('update', $this->business);

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'price' => $this->price,
            'price_type' => $this->price_type,
            'unit' => $this->unit,
            'is_available' => $this->is_available,
        ];

        if ($this->editingId) {
            $product = $this->business->products()->findOrFail($this->editingId);
            app(UpdateProduct::class)->handle($product, $data, $this->photos, [], Auth::user());
        } else {
            app(CreateProduct::class)->handle($this->business, $data, $this->photos, Auth::user());
        }

        unset($this->products);
        Flux::modal('product-form')->close();
        Flux::toast(variant: 'success', text: __('Producto guardado.'));
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
}; ?>

<section class="mx-auto w-full max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Productos y servicios') }}</flux:heading>

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
                            <img src="{{ $product->media->first()->url() }}" class="h-full w-full object-cover" alt="{{ $product->name }}">
                        @else
                            <div class="flex h-full w-full items-center justify-center">
                                <flux:icon.photo class="size-8 text-zinc-300 dark:text-zinc-600" variant="outline" />
                            </div>
                        @endif

                        <flux:badge size="sm" class="absolute top-2 right-2" :color="$product->status === 'publicado' ? 'green' : 'zinc'">
                            {{ ucfirst($product->status) }}
                        </flux:badge>
                    </div>

                    <div class="p-3">
                        <div class="truncate font-medium">{{ $product->name }}</div>
                        <div class="text-sm text-zinc-500">
                            {{ $product->type === 'producto' ? __('Producto') : __('Servicio') }}
                            @if ($product->price)
                                · ${{ number_format((float) $product->price, 0, ',', '.') }}
                            @endif
                        </div>

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

            <flux:select wire:model="type" :label="__('Tipo')">
                <flux:select.option value="producto">{{ __('Producto') }}</flux:select.option>
                <flux:select.option value="servicio">{{ __('Servicio') }}</flux:select.option>
            </flux:select>

            <flux:input wire:model="name" :label="__('Nombre')" required />
            <flux:textarea wire:model="description" :label="__('Descripción breve')" rows="3" />

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

            <div>
                <flux:text class="mb-2">{{ __('Fotos') }}</flux:text>
                <input type="file" wire:model="photos" multiple accept="image/*" class="block w-full text-sm">
                @error('photos') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
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
