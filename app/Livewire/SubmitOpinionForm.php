<?php

namespace App\Livewire;

use App\Domain\Businesses\Models\Business;
use App\Domain\Trust\Actions\SubmitOpinion;
use App\Domain\Trust\Models\Recommendation;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Livewire\Component;

/**
 * Pedido del usuario: cualquier usuario registrado debe poder dejar una
 * opinión sobre el negocio en la vitrina pública, sin haber comprado nada
 * — a diferencia de la recomendación ligada a un pedido ("Mis pedidos"),
 * esta no exige ninguna transacción previa. Un invitado que interactúa se
 * envía a iniciar sesión, mismo criterio que `FavoriteButton`.
 */
class SubmitOpinionForm extends Component
{
    public int $businessId;

    public string $body = '';

    public ?int $rating = null;

    /** @var array<int, string> */
    public array $tags = [];

    public bool $hasSubmitted = false;

    public function mount(Business $business): void
    {
        $this->businessId = (int) $business->getKey();
        $this->refreshSubmittedState();
    }

    private function refreshSubmittedState(): void
    {
        $this->hasSubmitted = Auth::check() && Recommendation::query()
            ->where('business_id', $this->businessId)
            ->where('author_user_id', Auth::id())
            ->whereNull('order_confirmation_id')
            ->whereIn('status', [Recommendation::PENDIENTE, Recommendation::PUBLICADA])
            ->exists();
    }

    public function submit(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        if ($this->rating === null) {
            $this->addError('rating', __('Elige una calificación de 1 a 5 estrellas.'));

            return;
        }

        try {
            app(SubmitOpinion::class)->handle(
                Business::findOrFail($this->businessId),
                Auth::user(),
                $this->body,
                $this->rating,
                $this->tags,
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('body', $e->getMessage());

            return;
        } catch (ValidationException $e) {
            $this->addError('body', $e->validator->errors()->first());

            return;
        }

        $this->reset(['body', 'rating', 'tags']);
        $this->hasSubmitted = true;

        Flux::toast(variant: 'success', text: __('¡Gracias! Tu opinión quedó enviada para revisión.'));
    }

    public function render(): View
    {
        return view('livewire.submit-opinion-form');
    }
}
