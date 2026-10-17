<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Campana de notificaciones del header del Cliente (pedido del usuario,
 * 2026-10-06): "agrega un sistema de notificaciones con un desplegable
 * en el header y quita el botón de mensajes, deja en las notificaciones
 * que algunos sean de tipo mensajes y que te lleven directamente al
 * mensaje que envían." No hace falta ningún dato nuevo — cada
 * `Notification::toArray()` del proyecto ya guarda `message`/`url`
 * (ver `BusinessMessageReceived`, `PointsAccrued`, etc.), así que este
 * desplegable es completamente genérico: solo lee las notificaciones
 * reales del usuario (`clientes/actividad`, que sigue existiendo para
 * el historial completo paginado) y, al hacer clic, marca como leída y
 * navega al `url` que cada notificación ya trae. Los mensajes nuevos
 * (`BusinessMessageReceived`) ya apuntan a `route('messages.show', ...)`
 * — la conversación exacta, no la bandeja general — así que "llevan
 * directamente al mensaje" sin código adicional.
 */
class NotificationBell extends Component
{
    /**
     * Mismo intervalo que el resto de widgets "en vivo" del proyecto sin
     * broadcasting real (`⚡messages.blade.php` usa `wire:poll.5s`); acá
     * 30s porque una notificación nueva no es tan urgente como un mensaje
     * dentro de una conversación ya abierta.
     */
    /**
     * @return Collection<int, DatabaseNotification>
     */
    #[Computed]
    public function notifications(): Collection
    {
        return Auth::user()->notifications()->latest()->take(10)->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function open(string $notificationId): void
    {
        /** @var DatabaseNotification|null $notification */
        $notification = Auth::user()->notifications()->whereKey($notificationId)->first();

        if (! $notification) {
            return;
        }

        if (! $notification->read_at) {
            $notification->markAsRead();
            unset($this->unreadCount);
        }

        if ($url = $notification->data['url'] ?? null) {
            $this->redirect($url, navigate: true);
        }
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications->markAsRead();

        unset($this->notifications, $this->unreadCount);
    }

    public function delete(string $notificationId): void
    {
        Auth::user()->notifications()->whereKey($notificationId)->delete();

        unset($this->notifications, $this->unreadCount);
    }

    public function render(): View
    {
        return view('livewire.notification-bell');
    }
}
