<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Actions\UpdateStorefront;
use App\Domain\Storefronts\Exceptions\IncompleteStorefrontException;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
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
new #[Title('Crea tu vitrina')] class extends Component {
    use WithFileUploads;

    public int $step = 1;

    public ?Business $business = null;

    // Paso 1
    public string $name = '';
    public ?string $whatsapp_number = '';
    public ?int $municipality_id = null;
    public ?int $category_id = null;
    public ?string $zone = '';

    // Paso 2
    public ?string $description = '';

    // Paso 3
    public $logo;
    public $cover;

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
            $this->category_id = $business->category_id;
            $this->zone = $business->zone;
            $this->description = $business->storefront?->description;
        }
    }

    public function goToStep2(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'zone' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->business) {
            $storefront = app(CreateStorefront::class)->handle(Auth::user(), $data);
            $this->business = $storefront->business;
        } else {
            app(UpdateStorefront::class)->handle($this->business, $data, Auth::user());
        }

        $this->step = 2;
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
            'cover' => $this->cover,
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
        $this->step = 5;
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
        return Category::where('is_active', true)->orderBy('name')->get();
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
            4 => __('Vista previa'),
            5 => __('¡Listo!'),
        ];
    @endphp

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
                    <div class="h-0.5 flex-1 {{ $i < 5 ? ($step > $i ? 'bg-brand-600' : 'bg-zinc-200 dark:bg-zinc-700') : 'bg-transparent' }}"></div>
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
                <flux:text class="mb-2">{{ __('Logo o foto principal') }}</flux:text>
                <input type="file" wire:model="logo" accept="image/*" class="block w-full text-sm">
                @if ($business?->logo_path && ! $logo)
                    <img src="{{ $business->logoUrl() }}" class="mt-2 size-16 rounded-lg object-cover" alt="{{ $business->name }}">
                @endif
                @error('logo') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
            </div>

            <div>
                <flux:text class="mb-2">{{ __('Portada de tu vitrina') }}</flux:text>
                <input type="file" wire:model="cover" accept="image/*" class="block w-full text-sm">
                @if ($business?->storefront?->cover_path && ! $cover)
                    <img src="{{ $business->storefront->coverUrl() }}" class="mt-2 h-24 w-full rounded-lg object-cover" alt="{{ __('Portada actual') }}">
                @endif
                @error('cover') <flux:text class="text-red-600">{{ $message }}</flux:text> @enderror
            </div>

            <div class="flex gap-3">
                <flux:button variant="ghost" wire:click="back">{{ __('Atrás') }}</flux:button>
                <flux:button variant="primary" class="flex-1" wire:click="goToStep4">{{ __('Siguiente') }}</flux:button>
            </div>
        </div>
    @elseif ($step === 4)
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('4. Revisa la vista previa') }}</flux:heading>

            <x-storefront-preview :business="$business" />

            <div class="flex gap-3">
                <flux:button variant="ghost" wire:click="back">{{ __('Editar información') }}</flux:button>
                <flux:button variant="primary" class="flex-1" wire:click="goToStep5">{{ __('Revisar y publicar') }}</flux:button>
            </div>
        </div>
    @else
        <div class="space-y-6">
            <flux:heading size="lg">{{ __('5. Publicación') }}</flux:heading>

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
                <flux:button variant="ghost" wire:click="editInformation">{{ __('Editar información') }}</flux:button>
                <flux:button variant="primary" class="flex-1" wire:click="publish">{{ __('Revisar y publicar') }}</flux:button>
            </div>
        </div>
    @endif
</section>
