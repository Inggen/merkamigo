<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Domain\Storefronts\Actions\UnpublishStorefront;
use App\Domain\Storefronts\Actions\UpdateStorefront;
use App\Domain\Storefronts\Exceptions\BusinessSuspendedException;
use App\Domain\Storefronts\Exceptions\IncompleteStorefrontException;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Editor seccionado de la vitrina (E04, 1.6 del TODO): Portada,
 * Información, Horarios, Ubicación, WhatsApp y Estado de publicación.
 * Reutiliza UpdateStorefront/PublishStorefront/UnpublishStorefront —
 * exactamente las mismas acciones que usa el wizard de 1.2.
 */
new #[Title('Editar mi vitrina')] class extends Component {
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $name = '';
    public ?string $whatsapp_number = '';
    public ?int $municipality_id = null;
    public ?int $category_id = null;
    public ?string $zone = '';
    public ?string $address = '';
    public ?string $headline = '';
    public ?string $description = '';
    public ?string $hours_text = '';
    public ?string $payment_info = '';
    public array $social_links = ['instagram' => '', 'facebook' => '', 'tiktok' => ''];

    public $logo;
    public $cover;

    /** @var array<int, string> */
    public array $missing = [];

    public ?string $savedAt = null;

    /**
     * El middleware `business.team` solo corre en la carga inicial de la
     * página: las peticiones AJAX de Livewire (save, publish, autosave en
     * `updated()`...) van al endpoint genérico `/livewire/update`, que no
     * pasa por esa ruta ni por ese middleware. `boot()` sí se ejecuta en
     * cada petición (inicial y subsecuentes), así que es el único lugar
     * donde fijar el team de forma confiable en todo el ciclo de vida del
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
        $this->name = $business->name;
        $this->whatsapp_number = $business->whatsapp_number;
        $this->municipality_id = $business->municipality_id;
        $this->category_id = $business->category_id;
        $this->zone = $business->zone;
        $this->address = $business->address;
        $this->headline = $business->storefront?->headline;
        $this->description = $business->storefront?->description;
        $this->hours_text = $business->hoursNote() ?? '';
        $this->payment_info = $business->payment_info;
        $this->social_links = array_merge($this->social_links, $business->social_links ?? []);
    }

    #[Computed]
    public function business(): Business
    {
        return Business::with('storefront')->findOrFail($this->businessId);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'zone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'payment_info' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->business);

        $data = $this->validate($this->rules());

        $data['hours'] = ['note' => $this->hours_text];
        $data['social_links'] = $this->social_links;

        if ($this->logo) {
            $data['logo'] = $this->logo;
        }

        if ($this->cover) {
            $data['cover'] = $this->cover;
        }

        app(UpdateStorefront::class)->handle($this->business, $data, Auth::user());
        $this->reset(['logo', 'cover']);
        $this->savedAt = now()->format('H:i');

        Flux::toast(variant: 'success', text: __('Cambios guardados.'));
    }

    /**
     * Autoguardado silencioso mientras el emprendedor edita (1.6 del TODO):
     * mismas reglas y acción que `save()`, pero sin bloquear con errores de
     * validación en cada tecla — un campo a medio llenar simplemente espera
     * a la siguiente edición para guardarse.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['logo', 'cover'], true)) {
            return;
        }

        $this->authorize('update', $this->business);

        $validator = Validator::make($this->only(array_keys($this->rules())), $this->rules());

        if ($validator->fails()) {
            return;
        }

        $data = $validator->validated();
        $data['hours'] = ['note' => $this->hours_text];
        $data['social_links'] = $this->social_links;

        app(UpdateStorefront::class)->handle($this->business, $data, Auth::user());
        $this->savedAt = now()->format('H:i');
    }

    public function publish(): void
    {
        $this->authorize('update', $this->business);

        try {
            app(PublishStorefront::class)->handle($this->business, Auth::user());
            $this->missing = [];
            Flux::toast(variant: 'success', text: __('¡Tu vitrina está publicada!'));
        } catch (IncompleteStorefrontException $e) {
            $this->missing = $e->missing;
        } catch (BusinessSuspendedException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    public function unpublish(): void
    {
        $this->authorize('update', $this->business);

        app(UnpublishStorefront::class)->handle($this->business, Auth::user());
        Flux::toast(text: __('Tu vitrina volvió a borrador.'));
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

<section class="mx-auto w-full max-w-3xl space-y-8">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Editar mi vitrina') }}</flux:heading>

        <div class="flex items-center gap-3">
            @if ($this->business->isPublished())
                <flux:badge color="green">{{ __('Publicado') }}</flux:badge>
            @elseif ($this->business->isSuspended())
                <flux:badge color="red">{{ __('Suspendido') }}</flux:badge>
            @else
                <flux:badge>{{ ucfirst($this->business->status) }}</flux:badge>
            @endif

            <flux:button size="sm" variant="ghost" icon="users" :href="route('emprendedores.negocios.colaboradores', $this->business)" wire:navigate>
                {{ __('Colaboradores') }}
            </flux:button>
        </div>
    </div>

    @if ($this->business->isSuspended())
        <div class="rounded-xl border border-red-300 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
            <flux:text class="font-medium">{{ __('Esta vitrina está suspendida y no se puede volver a publicar desde aquí.') }}</flux:text>
            <flux:text class="mt-1">{{ $this->business->suspension_reason }}</flux:text>
            <flux:text class="mt-2 text-sm text-zinc-500">
                {{ __('Si crees que es un error, contáctanos por soporte.') }}
            </flux:text>
        </div>
    @endif

    @if ($missing !== [])
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-950">
            <flux:text class="font-medium">{{ __('Te falta completar para publicar:') }}</flux:text>
            <ul class="mt-2 list-inside list-disc text-sm">
                @foreach ($missing as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="save" class="space-y-8">
        <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Portada') }}</flux:heading>

            <div>
                <flux:text class="mb-2">{{ __('Logo o foto principal') }}</flux:text>
                <input type="file" wire:model="logo" accept="image/*" class="block w-full text-sm">
                @if ($this->business->logoUrl() && ! $logo)
                    <img src="{{ $this->business->logoUrl() }}" class="mt-2 size-16 rounded-lg object-cover" alt="{{ $this->business->name }}">
                @endif
            </div>

            <div>
                <flux:text class="mb-2">{{ __('Portada de la vitrina') }}</flux:text>
                <input type="file" wire:model="cover" accept="image/*" class="block w-full text-sm">
                @if ($this->business->storefront?->coverUrl() && ! $cover)
                    <img src="{{ $this->business->storefront->coverUrl() }}" class="mt-2 h-24 w-full rounded-lg object-cover" alt="{{ __('Portada actual') }}">
                @endif
            </div>
        </div>

        <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Información') }}</flux:heading>

            <flux:input wire:model.live.debounce.900ms="name" :label="__('Nombre del negocio')" required />
            <flux:input wire:model.live.debounce.900ms="headline" :label="__('Frase corta')" />
            <flux:textarea wire:model.live.debounce.900ms="description" :label="__('Descripción')" rows="4" />

            <flux:select wire:model.live="municipality_id" :label="__('Municipio')">
                <flux:select.option value="">{{ __('Selecciona un municipio') }}</flux:select.option>
                @foreach ($this->municipalities as $municipality)
                    <flux:select.option value="{{ $municipality->id }}">{{ $municipality->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="category_id" :label="__('Categoría')">
                <flux:select.option value="">{{ __('Selecciona una categoría') }}</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live.debounce.900ms="zone" :label="__('Zona o barrio')" />
            <flux:input wire:model.live.debounce.900ms="address" :label="__('Dirección (opcional)')" />
        </div>

        <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('Horarios') }}</flux:heading>
            <flux:textarea wire:model.live.debounce.900ms="hours_text" rows="2" placeholder="{{ __('Ej: Lun-Sáb 8:00am - 6:00pm') }}" />
        </div>

        <div class="space-y-4 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            <flux:heading size="lg">{{ __('WhatsApp y redes') }}</flux:heading>

            <flux:input wire:model.live.debounce.900ms="whatsapp_number" :label="__('WhatsApp')" type="tel" placeholder="+57 300 000 0000" />
            <flux:input wire:model.live.debounce.900ms="social_links.instagram" label="Instagram" placeholder="https://instagram.com/..." />
            <flux:input wire:model.live.debounce.900ms="social_links.facebook" label="Facebook" placeholder="https://facebook.com/..." />
            <flux:input wire:model.live.debounce.900ms="social_links.tiktok" label="TikTok" placeholder="https://tiktok.com/@..." />
            <flux:textarea wire:model.live.debounce.900ms="payment_info" :label="__('Información de pago (opcional)')" rows="2" placeholder="{{ __('Ej: Nequi 300 000 0000, o enlace de pago') }}" />
        </div>

        <div class="flex items-center gap-3">
            <flux:button type="submit" variant="primary">{{ __('Guardar cambios') }}</flux:button>

            @if ($savedAt)
                <flux:text class="text-sm text-zinc-400" wire:loading.remove wire:target="save">
                    {{ __('Guardado automáticamente a las :hora', ['hora' => $savedAt]) }}
                </flux:text>
            @endif

            @if ($this->business->isPublished())
                <flux:button variant="ghost" wire:click="unpublish" wire:confirm="{{ __('¿Volver esta vitrina a borrador?') }}">
                    {{ __('Volver a borrador') }}
                </flux:button>
            @elseif (! $this->business->isSuspended())
                <flux:button variant="primary" wire:click="publish">{{ __('Publicar') }}</flux:button>
            @endif
        </div>
    </form>
</section>
