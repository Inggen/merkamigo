<?php

namespace App\Livewire;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Actions\RecordStoryView;
use App\Domain\Social\Models\Story;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Carrusel de Estados en la parte superior del feed (Fase 3 del TODO
 * social, Sprint 3). El visor va en el mismo componente — abrir/cerrar y
 * avanzar es estado de UI puro, no necesita una página aparte.
 */
class StoriesRail extends Component
{
    public ?int $municipalityId = null;

    public ?int $viewingBusinessId = null;

    public int $viewingIndex = 0;

    public function mount(?Municipality $municipality = null): void
    {
        $this->municipalityId = $municipality?->id;
    }

    public function open(int $businessId): void
    {
        $this->viewingBusinessId = $businessId;
        $this->viewingIndex = 0;
        $this->recordView();
    }

    public function close(): void
    {
        $this->viewingBusinessId = null;
        $this->viewingIndex = 0;
    }

    /**
     * Pedido del usuario (2026-10-16): poder crear un estado desde
     * Comunidad sin salir del feed, con la misma modal que en la zona de
     * emprendedores — ver `CreateStoryModal`, embebida aquí cuando el
     * usuario administra algún negocio.
     */
    #[Computed]
    public function ownBusiness(): ?Business
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        // Igual que en `FeedController::canCreateStory()`: `BusinessPolicy::update`
        // revisa el rol dentro del "team" activo de spatie/laravel-permission,
        // así que hay que fijarlo a cada negocio candidato antes de preguntar.
        $business = $user->businesses->first(function (Business $candidate) use ($user) {
            setPermissionsTeamId($candidate->id);
            $user->unsetRelation('roles');

            return $user->can('update', $candidate);
        });

        setPermissionsTeamId(null);
        $user->unsetRelation('roles');

        return $business;
    }

    #[On('story-created')]
    public function refreshAfterStoryCreated(): void
    {
        unset($this->businessesWithStories);
    }

    public function next(): void
    {
        $stories = $this->currentBusinessStories();

        if ($this->viewingIndex + 1 >= $stories->count()) {
            $this->close();

            return;
        }

        $this->viewingIndex++;
        $this->recordView();
    }

    public function previous(): void
    {
        if ($this->viewingIndex === 0) {
            return;
        }

        $this->viewingIndex--;
        $this->recordView();
    }

    private function recordView(): void
    {
        if (! Auth::check()) {
            return;
        }

        $story = $this->currentBusinessStories()->get($this->viewingIndex);

        if ($story) {
            app(RecordStoryView::class)->handle(Auth::user(), $story);
        }
    }

    /**
     * @return Collection<int, Story>
     */
    private function currentBusinessStories()
    {
        return $this->businessesWithStories
            ->firstWhere('id', $this->viewingBusinessId)['stories']
            ?? collect();
    }

    /**
     * Negocios con estados activos, agrupados, con estado visto/no visto
     * para el usuario actual (anillo de color vs. gris).
     *
     * @return Collection<int, array{id: int, name: string, logo: ?string, stories: Collection<int, Story>, allSeen: bool}>
     */
    #[Computed]
    public function businessesWithStories()
    {
        $stories = Story::query()
            ->where('expires_at', '>', now())
            ->when($this->municipalityId, fn ($q) => $q->whereHas('business', fn ($b) => $b->servesMunicipality($this->municipalityId)))
            ->with(['business', 'views', 'activePromotion'])
            ->orderBy('created_at')
            ->get()
            ->sortByDesc(fn (Story $story) => $story->activePromotion !== null)
            ->groupBy('business_id');

        $user = Auth::user();

        return $stories->map(function ($businessStories) use ($user) {
            $business = $businessStories->first()->business;

            return [
                'id' => $business->id,
                'name' => $business->name,
                'logo' => $business->logoUrl(),
                'stories' => $businessStories->values(),
                'allSeen' => $user ? $businessStories->every(fn (Story $story) => $story->isViewedBy($user)) : false,
            ];
        })->values();
    }

    public function render(): View
    {
        return view('livewire.stories-rail');
    }
}
