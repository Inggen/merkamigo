<?php

namespace App\Livewire;

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Actions\CreateStory;
use App\Domain\Social\Actions\GenerateSocialSalesCopy;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Modal "Crear nuevo estado", extraído de la página de Estados del
 * emprendedor (Fase 3 del TODO social, Sprint 3) para poder reutilizarlo
 * también en Comunidad (pedido del usuario, 2026-10-16): el dueño de un
 * negocio debe poder publicar un estado sin salir del feed, con la misma
 * modal — por eso vive acá en vez de duplicarse en cada sitio que la usa.
 */
class CreateStoryModal extends Component
{
    use WithFileUploads;

    #[Locked]
    public int $businessId;

    public string $type = 'promocion';

    public string $format = 'imagen';

    public ?string $caption = '';

    public $photo;

    public $video;

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
    public function availableProducts()
    {
        return $this->business->products()->where('status', 'publicado')->orderBy('name')->get();
    }

    public function updatedFormat(): void
    {
        // Cambiar de Imagen a Video (o viceversa) limpia lo que se
        // hubiera seleccionado para el otro formato — evita que un
        // archivo elegido y luego "escondido" se publique sin que el
        // dueño lo note.
        $this->reset(['photo', 'video']);
        $this->resetErrorBag(['photo', 'video']);
    }

    public function save(): void
    {
        $this->authorize('update', $this->business);

        $media = $this->format === 'video' ? $this->video : $this->photo;

        if (! $media) {
            $this->addError(
                $this->format === 'video' ? 'video' : 'photo',
                $this->format === 'video' ? __('Agrega un video para tu estado.') : __('Agrega una foto para tu estado.'),
            );

            return;
        }

        try {
            app(CreateStory::class)->handle($this->business, [
                'type' => $this->type,
                'caption' => $this->caption,
                'product_id' => $this->product_id,
            ], $media, Auth::user());
        } catch (ValidationException $e) {
            $this->addError('caption', $e->validator->errors()->first());

            return;
        }

        $this->reset(['caption', 'photo', 'video', 'product_id']);
        $this->type = 'promocion';
        $this->format = 'imagen';

        // Cualquier listado de estados en pantalla (la propia página de
        // Estados, el carrusel de Comunidad) necesita enterarse para
        // refrescarse — este componente puede vivir en cualquiera de los
        // dos, o en ambos por separado nunca a la vez.
        $this->dispatch('story-created', businessId: $this->businessId);

        Flux::modal('create-story-'.$this->businessId)->close();
        Flux::toast(variant: 'success', text: __('¡Estado publicado! Estará visible por 24 horas.'));
    }

    public function removePhoto(): void
    {
        $this->reset('photo');
        $this->resetErrorBag('photo');
    }

    public function removeVideo(): void
    {
        $this->reset('video');
        $this->resetErrorBag('video');
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

    public function render(): View
    {
        return view('livewire.create-story-modal');
    }
}
