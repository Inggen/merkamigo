<?php

namespace App\Livewire;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Events\Actions\CreateEventAttendance;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\PublicEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Widget "Reserva tu cupo" embebido en la ficha de un evento público
 * (pedido del usuario, 2026-10-08): reserva un lugar PARA asistir a este
 * evento (según cupo/disponibilidad), distinto del widget "Reservar
 * este espacio" (`pages::eventos.reservar`, que reserva el espacio del
 * negocio para un evento privado propio).
 *
 * @property-read PublicEvent $event
 */
class EventAttendanceWidget extends Component
{
    public int $eventId;

    public int $quantity = 1;

    public string $attendeeName = '';

    public string $attendeeEmail = '';

    public string $attendeePhone = '';

    public ?int $createdAttendanceId = null;

    public ?string $ticketUrl = null;

    public bool $isPaid = false;

    public function mount(PublicEvent $event): void
    {
        $this->eventId = $event->id;

        if (Auth::check()) {
            $this->attendeeName = Auth::user()->name;
            $this->attendeeEmail = Auth::user()->email;
        }
    }

    public function getEventProperty(): PublicEvent
    {
        return PublicEvent::findOrFail($this->eventId);
    }

    public function submit(): void
    {
        $throttleKey = 'event-attendance:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('submit', __('Demasiados intentos. Espera unos minutos y vuelve a intentar.'));

            return;
        }

        RateLimiter::increment($throttleKey, 600);

        $this->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'attendeeName' => ['required', 'string', 'max:120'],
            'attendeeEmail' => ['required', 'email', 'max:190'],
            'attendeePhone' => ['required', 'string', 'max:32'],
        ]);

        try {
            $attendance = app(CreateEventAttendance::class)->handle(
                $this->event,
                $this->quantity,
                $this->attendeeName,
                $this->attendeeEmail,
                $this->attendeePhone,
                Auth::user(),
                (string) Str::uuid(),
            );
        } catch (EventActionException $e) {
            $this->addError('submit', $e->getMessage());

            return;
        }

        $this->createdAttendanceId = $attendance->id;
        $this->isPaid = ! $attendance->isFree();

        if (! $this->isPaid) {
            $this->ticketUrl = URL::signedRoute('eventos.entradas.show', ['eventAttendance' => $attendance->id]);
        }

        app(RegisterAnalyticsEvent::class)->handle($this->event->business, AnalyticsEvent::EVENT_RESERVATION_STARTED, $this->event, request());
    }

    public function render(): View
    {
        return view('livewire.event-attendance-widget');
    }
}
