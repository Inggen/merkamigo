<?php

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateStorefront;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Versión mínima de "Mi Merkamigo en cinco minutos" (E02) que demuestra el
 * criterio de aceptación de 0.4: la misma acción `CreateStorefront` se usa
 * aquí, en la API y en las pruebas Pest. El flujo completo de cinco pasos
 * con audio/fotos pertenece a la Fase 1 (1.2 del TODO).
 */
new #[Title('Crea tu vitrina')] class extends Component {
    public string $name = '';
    public ?string $whatsapp_number = '';
    public ?int $municipality_id = null;
    public ?int $category_id = null;
    public ?string $headline = '';
    public ?string $description = '';

    public function create(CreateStorefront $createStorefront): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $storefront = $createStorefront->handle(Auth::user(), $data);

        Flux::toast(variant: 'success', text: __('¡Tu vitrina fue creada en borrador!'));

        $this->redirectRoute('dashboard', navigate: true);

        unset($storefront);
    }

    #[Computed]
    public function municipalities()
    {
        return Municipality::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function categories()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }
}; ?>

<section class="w-full max-w-2xl mx-auto">
    <flux:heading size="xl">{{ __('Mi Merkamigo en cinco minutos') }}</flux:heading>
    <flux:subheading class="mb-6">
        {{ __('Datos mínimos para dejar tu vitrina en borrador. Podrás completar fotos, horarios y productos después.') }}
    </flux:subheading>

    <form wire:submit="create" class="space-y-6">
        <flux:input wire:model="name" :label="__('Nombre del negocio')" required autofocus />

        <flux:input wire:model="whatsapp_number" :label="__('WhatsApp')" type="tel" placeholder="+57 300 000 0000" />

        <flux:select wire:model="municipality_id" :label="__('Municipio')">
            <flux:select.option value="">{{ __('Selecciona un municipio') }}</flux:select.option>
            @foreach ($this->municipalities as $municipality)
                <flux:select.option value="{{ $municipality->id }}">{{ $municipality->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="category_id" :label="__('Categoría')">
            <flux:select.option value="">{{ __('Selecciona una categoría') }}</flux:select.option>
            @foreach ($this->categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="headline" :label="__('Frase corta (opcional)')" />

        <flux:textarea wire:model="description" :label="__('Describe tu negocio (opcional)')" rows="3" />

        <flux:button type="submit" variant="primary" class="w-full">
            {{ __('Crear mi vitrina en borrador') }}
        </flux:button>
    </form>
</section>
