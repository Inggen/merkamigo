<?php

use App\Domain\Businesses\Models\Business;
use App\Domain\Immersive\Actions\AssignBusinessToStand;
use App\Domain\Immersive\Models\ImmersiveObjectTemplate;
use App\Domain\Immersive\Models\StandAssignment;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * IMM-021 del TODO inmersivo: "Mi stand en la plaza" — el emprendedor
 * elige entre las plantillas publicadas del catálogo. No puede mover ni
 * redimensionar su stand (eso lo decide el slot que le asignó
 * `AssignBusinessToStand`, IMM-022) — solo cambia CUÁL de las plantillas
 * ocupa ese espacio.
 */
new #[Title('Mi stand en la plaza')] class extends Component {
    #[Locked]
    public int $businessId;

    /**
     * Igual que en `⚡vitrina.blade.php`: las peticiones Livewire (elegir
     * plantilla) no pasan por el middleware `business.team` de la ruta,
     * así que `boot()` es el único lugar confiable para fijar el equipo de
     * permisos en todo el ciclo de vida del componente.
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
    public function assignment(): ?StandAssignment
    {
        return StandAssignment::with(['slot.zone.plaza', 'template'])
            ->where('business_id', $this->businessId)
            ->first();
    }

    #[Computed]
    public function templates()
    {
        return ImmersiveObjectTemplate::where('category', 'stand')
            ->where('status', 'publicada')
            ->orderBy('id')
            ->get();
    }

    /**
     * Cambiar de plantilla reutiliza el mismo asignador de IMM-022: si el
     * slot actual la soporta, la conserva (mismo stand, sin duplicarlo);
     * si no, busca uno compatible. Nunca cambia posición ni dimensiones.
     */
    public function chooseTemplate(int $templateId): void
    {
        $business = $this->business;
        $this->authorize('update', $business);

        $assignment = StandAssignment::firstOrCreate(['business_id' => $business->id]);
        $assignment->update(['object_template_id' => $templateId]);

        app(AssignBusinessToStand::class)->handle($business);
        unset($this->assignment, $this->templates);

        Flux::toast(variant: 'success', text: __('Tu stand se actualizó.'));
    }
}; ?>

<section class="mx-auto w-full max-w-5xl">
    <div class="mb-6">
        <flux:heading size="xl">{{ __('Mi stand en la plaza') }}</flux:heading>
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            {{ __('Elige cómo se ve tu espacio en la plaza inmersiva de tu municipio. No puedes cambiar su ubicación ni su tamaño — eso lo asigna Merkamigo.') }}
        </flux:text>
    </div>

    @php($assignment = $this->assignment)

    <div class="mb-6 rounded-2xl border border-zinc-200 p-4 dark:border-zinc-700">
        @if (! $this->business->isPublished())
            <flux:badge>{{ __('Publica tu vitrina primero') }}</flux:badge>
            <flux:text class="mt-2">{{ __('Cuando tu vitrina esté publicada, te asignaremos un espacio automáticamente en la plaza inmersiva de tu municipio.') }}</flux:text>
        @elseif (! $assignment || $assignment->status === 'sin_configurar')
            <flux:badge>{{ __('Sin configurar') }}</flux:badge>
            <flux:text class="mt-2">{{ __('Todavía no se ha intentado asignar un espacio para tu negocio.') }}</flux:text>
        @elseif ($assignment->status === 'pendiente')
            <flux:badge color="amber">{{ __('Pendiente') }}</flux:badge>
            <flux:text class="mt-2">{{ __('Tu municipio todavía no tiene una experiencia inmersiva activa. En cuanto esté lista, se te asignará un espacio.') }}</flux:text>
        @elseif ($assignment->status === 'sin_cupo')
            <flux:badge color="amber">{{ __('Sin cupo') }}</flux:badge>
            <flux:text class="mt-2">{{ __('En este momento no hay espacio disponible en las plazas activas de tu municipio. Te avisaremos cuando se libere uno.') }}</flux:text>
        @elseif ($assignment->status === 'pausado')
            <flux:badge>{{ __('En pausa') }}</flux:badge>
            <flux:text class="mt-2">{{ __('Tu espacio quedó en pausa porque tu vitrina no está publicada. Vuelve a publicarla para recuperarlo.') }}</flux:text>
        @elseif ($assignment->status === 'reubicacion_requerida')
            <flux:badge color="amber">{{ __('Reubicación en curso') }}</flux:badge>
            <flux:text class="mt-2">{{ __('Tu espacio anterior ya no está disponible. Estamos buscando uno nuevo compatible.') }}</flux:text>
        @elseif ($assignment->status === 'publicado')
            <flux:badge color="green">{{ __('Publicado') }}</flux:badge>
            <flux:text class="mt-2">
                {{ __('Tu stand está activo en :plaza, espacio :codigo.', [
                    'plaza' => $assignment->slot?->zone?->plaza?->name ?? '—',
                    'codigo' => $assignment->slot?->code ?? '—',
                ]) }}
            </flux:text>
        @endif
    </div>

    @if ($this->templates->isEmpty())
        <flux:text class="text-zinc-500">{{ __('Todavía no hay plantillas de stand disponibles.') }}</flux:text>
    @else
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ($this->templates as $template)
                @php($isSelected = $assignment?->object_template_id === $template->id)

                <div class="flex flex-col items-center gap-3 rounded-2xl border p-4 text-center {{ $isSelected ? 'border-brand-500 ring-1 ring-brand-500' : 'border-zinc-200 dark:border-zinc-700' }}">
                    @if ($template->thumbnailUrl())
                        <img src="{{ $template->thumbnailUrl() }}" alt="{{ $template->name }}" class="h-28 w-full rounded-xl object-cover">
                    @else
                        <div class="flex h-28 w-full items-center justify-center rounded-xl bg-zinc-100 text-zinc-400 dark:bg-zinc-800">
                            <flux:icon.cube class="size-8" variant="outline" />
                        </div>
                    @endif

                    <flux:heading size="sm">{{ $template->name }}</flux:heading>
                    <flux:text class="text-xs text-zinc-500">{{ $template->max_width }}m × {{ $template->max_depth }}m</flux:text>

                    @if ($isSelected)
                        <flux:badge color="green" size="sm">{{ __('Seleccionado') }}</flux:badge>
                    @else
                        <flux:button size="sm" variant="ghost" wire:click="chooseTemplate({{ $template->id }})">
                            {{ __('Elegir') }}
                        </flux:button>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</section>
