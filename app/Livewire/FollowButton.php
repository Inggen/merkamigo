<?php

namespace App\Livewire;

use App\Domain\Businesses\Models\Business;
use App\Domain\Social\Actions\ToggleFollowBusiness;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Botón "Seguir" de un negocio (2.3 del TODO social, Sprint 2). Mismo
 * patrón que `FavoriteButton` — un invitado que hace clic se envía a
 * iniciar sesión.
 */
class FollowButton extends Component
{
    public int $businessId;

    public bool $following = false;

    public bool $compact = false;

    public function mount(Business $business, bool $compact = false): void
    {
        $this->businessId = $business->id;
        $this->following = Auth::user()?->isFollowing($business) ?? false;
        $this->compact = $compact;
    }

    public function toggle(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $business = Business::findOrFail($this->businessId);

        $this->following = app(ToggleFollowBusiness::class)->handle(Auth::user(), $business);
    }

    public function render(): View
    {
        return view('livewire.follow-button');
    }
}
