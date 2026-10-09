<?php

namespace App\Domain\Marketplace\Notifications;

use App\Domain\Marketplace\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * Confirmación de un pedido pagado SIN cuenta (PR3 de
 * TODO_VENTAS_RENTABILIDAD.md, P0.2) — el invitado no tiene a dónde
 * entrar a ver "Mis compras", así que se le avisa por correo con
 * `Notification::route('mail', ...)` (on-demand, sin `User`), mismo
 * patrón que `App\Domain\Events\Notifications\EventAttendanceConfirmedForAttendee`.
 * El enlace de "ver mi pedido" es una URL firmada (`signed`): solo quien
 * recibió este correo puede abrirlo, nunca adivinable por id.
 */
class GuestOrderPaid extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->fresh(['business', 'product']);
        $confirmationUrl = URL::signedRoute('marketplace.guest.confirmation', ['order' => $order->id]);

        $message = (new MailMessage)
            ->subject(__('Tu pedido en :business', ['business' => $order->business->name]))
            ->greeting(__('¡Hola :name!', ['name' => $order->guest_name]))
            ->line(__('Tu pago de ":product" quedó confirmado.', ['product' => $order->product->name]))
            ->line(__('Total pagado: $:amount', ['amount' => number_format($order->amount_cents / 100, 0, ',', '.')]));

        if ($order->product->isDigital()) {
            $message->line(__('Puedes descargarlo desde el enlace de abajo.'));
        }

        return $message
            ->action(__('Ver mi pedido'), $confirmationUrl)
            ->line(__(':business también lo verá en sus ventas y se pondrá en contacto si necesita coordinar algo contigo.', ['business' => $order->business->name]));
    }
}
