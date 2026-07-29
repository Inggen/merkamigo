<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessAttribute;
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

    /** @var array<string, array{closed: bool, open: ?string, close: ?string}> */
    public array $schedule = [];

    public ?string $payment_info = '';
    public array $social_links = ['instagram' => '', 'facebook' => '', 'tiktok' => ''];

    /** @var array<int, string> */
    public array $business_attributes = [];

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
        $this->business_attributes = $business->attributes ?? [];

        $defaultSchedule = [];
        foreach (Business::DAY_LABELS as $day => $label) {
            $defaultSchedule[$day] = ['closed' => false, 'open' => null, 'close' => null];
        }
        $this->schedule = array_replace_recursive($defaultSchedule, $business->hours['schedule'] ?? []);
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

        $data['hours'] = ['note' => $this->hours_text, 'schedule' => $this->schedule];
        $data['social_links'] = $this->social_links;
        $data['attributes'] = $this->business_attributes;

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
        $data['hours'] = ['note' => $this->hours_text, 'schedule' => $this->schedule];
        $data['social_links'] = $this->social_links;
        $data['attributes'] = $this->business_attributes;

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
        return Category::where('is_active', true)->orderBy('position')->get();
    }

    #[Computed]
    public function attributeOptions()
    {
        return BusinessAttribute::where('is_active', true)->orderBy('name')->get();
    }
}; ?>

<section class="mx-auto w-full max-w-5xl" x-data="{ section: 'portada' }">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Edita tu vitrina') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Personaliza la información de tu negocio.') }}</flux:text>
        </div>

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

    @if ($savedAt)
        <div class="mb-4 flex items-center gap-1.5 text-sm text-zinc-400" wire:loading.remove wire:target="save">
            <flux:icon.check-circle class="size-4" variant="outline" />
            {{ __('Guardado automáticamente a las :hora', ['hora' => $savedAt]) }}
        </div>
    @endif

    <form wire:submit="save" class="grid gap-6 lg:grid-cols-[14rem_1fr]">
        <nav class="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
            @foreach ([
                'portada' => ['label' => __('Portada'), 'icon' => 'photo'],
                'informacion' => ['label' => __('Información'), 'icon' => 'information-circle'],
                'horarios' => ['label' => __('Horarios'), 'icon' => 'clock'],
                'ubicacion' => ['label' => __('Ubicación'), 'icon' => 'map-pin'],
                'whatsapp' => ['label' => __('WhatsApp'), 'icon' => 'chat-bubble-left-right'],
                'estado' => ['label' => __('Estado de publicación'), 'icon' => 'rocket-launch'],
            ] as $key => $tab)
                <button
                    type="button"
                    x-on:click="section = '{{ $key }}'"
                    :class="section === '{{ $key }}' ? 'bg-brand-50 text-brand-600 dark:bg-brand-950' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                    class="flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-medium"
                >
                    <x-dynamic-component :component="'flux::icon.'.$tab['icon']" class="size-4 shrink-0" variant="outline" />
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        <div class="min-w-0 rounded-2xl border border-zinc-200 p-6 dark:border-zinc-700">
            @if ($this->business->isSuspended())
                <div class="mb-6 rounded-xl border border-red-300 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                    <flux:text class="font-medium">{{ __('Esta vitrina está suspendida y no se puede volver a publicar desde aquí.') }}</flux:text>
                    <flux:text class="mt-1">{{ $this->business->suspension_reason }}</flux:text>
                    <flux:text class="mt-2 text-sm text-zinc-500">
                        {{ __('Si crees que es un error, contáctanos por soporte.') }}
                    </flux:text>
                </div>
            @endif

            <div x-show="section === 'portada'" class="space-y-4">
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

            <div x-show="section === 'informacion'" x-cloak class="space-y-4">
                <flux:heading size="lg">{{ __('Información básica') }}</flux:heading>

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

                @if ($this->attributeOptions->isNotEmpty())
                    <flux:checkbox.group wire:model.live="attributes" :label="__('Atributos')">
                        @foreach ($this->attributeOptions as $option)
                            <flux:checkbox value="{{ $option->slug }}" :label="$option->name" />
                        @endforeach
                    </flux:checkbox.group>
                @endif
            </div>

            <div x-show="section === 'horarios'" x-cloak class="space-y-4">
                <flux:heading size="lg">{{ __('Horarios') }}</flux:heading>
                <flux:textarea wire:model.live.debounce.900ms="hours_text" rows="2" placeholder="{{ __('Ej: Lun-Sáb 8:00am - 6:00pm') }}" />

                <div class="space-y-2">
                    <flux:text class="font-medium">{{ __('Horario por día (opcional, permite mostrar "Abierto ahora" en tu vitrina)') }}</flux:text>

                    @foreach (\App\Domain\Businesses\Models\Business::DAY_LABELS as $day => $label)
                        <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 pb-2 dark:border-zinc-800">
                            <span class="w-24 shrink-0 text-sm font-medium">{{ $label }}</span>

                            <flux:checkbox wire:model.live="schedule.{{ $day }}.closed" :label="__('Cerrado')" />

                            @if (! ($schedule[$day]['closed'] ?? false))
                                <input type="time" wire:model.live.debounce.900ms="schedule.{{ $day }}.open" class="rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                                <span class="text-sm text-zinc-400">{{ __('a') }}</span>
                                <input type="time" wire:model.live.debounce.900ms="schedule.{{ $day }}.close" class="rounded-lg border-zinc-300 text-sm dark:border-zinc-700 dark:bg-zinc-800">
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div x-show="section === 'ubicacion'" x-cloak class="space-y-4">
                <flux:heading size="lg">{{ __('Ubicación') }}</flux:heading>
                <flux:input wire:model.live.debounce.900ms="zone" :label="__('Zona o barrio')" />
                <flux:input wire:model.live.debounce.900ms="address" :label="__('Dirección (opcional)')" />
            </div>

            <div x-show="section === 'whatsapp'" x-cloak class="space-y-4">
                <flux:heading size="lg">{{ __('WhatsApp y redes') }}</flux:heading>

                <flux:input wire:model.live.debounce.900ms="whatsapp_number" :label="__('WhatsApp')" type="tel" placeholder="+57 300 000 0000" />
                <flux:input wire:model.live.debounce.900ms="social_links.instagram" label="Instagram" placeholder="https://instagram.com/..." />
                <flux:input wire:model.live.debounce.900ms="social_links.facebook" label="Facebook" placeholder="https://facebook.com/..." />
                <flux:input wire:model.live.debounce.900ms="social_links.tiktok" label="TikTok" placeholder="https://tiktok.com/@..." />
                <flux:textarea wire:model.live.debounce.900ms="payment_info" :label="__('Información de pago (opcional)')" rows="2" placeholder="{{ __('Ej: Nequi 300 000 0000, o enlace de pago') }}" />
            </div>

            <div x-show="section === 'estado'" x-cloak class="space-y-4">
                <flux:heading size="lg">{{ __('Estado de publicación') }}</flux:heading>

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

                <div class="flex items-center gap-3">
                    @if ($this->business->isPublished())
                        <flux:button variant="ghost" wire:click="unpublish" wire:confirm="{{ __('¿Volver esta vitrina a borrador?') }}">
                            {{ __('Volver a borrador') }}
                        </flux:button>
                    @elseif (! $this->business->isSuspended())
                        <flux:button variant="primary" wire:click="publish">{{ __('Publicar') }}</flux:button>
                    @endif
                </div>
            </div>

            <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:button type="submit" variant="primary">{{ __('Guardar cambios') }}</flux:button>
            </div>
        </div>
    </form>
</section>
