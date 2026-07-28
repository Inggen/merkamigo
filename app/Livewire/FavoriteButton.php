<?php

namespace App\Livewire;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Actions\ToggleFavorite;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Botón de "Guardar en favoritos" embebido en la vitrina pública y el
 * detalle de producto (1.1.1/1.3 del TODO). Un invitado que hace clic se
 * envía a iniciar sesión — favoritos es una de las acciones que sí exige
 * cuenta (1.1.1: "solicitar registro únicamente para acciones que deban
 * guardarse").
 */
class FavoriteButton extends Component
{
    public string $favoritableType;

    public int $favoritableId;

    public bool $favorited = false;

    /**
     * Variante compacta (solo ícono, sin texto ni fondo) para superponer
     * en la esquina de una tarjeta de negocio/producto.
     */
    public bool $compact = false;

    public function mount(Business|Product $favoritable, bool $compact = false): void
    {
        $this->favoritableType = $favoritable->getMorphClass();
        $this->favoritableId = (int) $favoritable->getKey();
        $this->favorited = $favoritable->isFavoritedBy(Auth::user());
        $this->compact = $compact;
    }

    public function toggle(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $favoritable = $this->favoritableType::findOrFail($this->favoritableId);

        $this->favorited = app(ToggleFavorite::class)->handle(Auth::user(), $favoritable);
    }

    public function render(): View
    {
        return view('livewire.favorite-button');
    }
}
