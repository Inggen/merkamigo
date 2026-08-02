<?php

namespace App\Domain\Analytics\Notifications;

use App\Domain\Businesses\Models\Business;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Informe semanal por correo (4.5 del TODO) — primer uso real del canal
 * `mail` en el proyecto. Sin SMTP configurado, `MAIL_MAILER=log` (valor por
 * defecto de `.env.example`) escribe el correo al log en vez de fallar, así
 * que este envío es seguro incluso en un entorno sin credenciales de
 * correo reales.
 */
class WeeklyBusinessReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $metrics
     */
    public function __construct(private readonly Business $business, private readonly array $metrics) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', PushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Tu resumen semanal de :business', ['business' => $this->business->name]))
            ->greeting(__('¡Hola!'))
            ->line($this->metrics['summary'])
            ->line(__(':orders pedidos completados esta semana.', ['orders' => $this->metrics['completed_orders']]))
            ->action(__('Ver mis métricas'), route('emprendedores.negocios.metricas', $this->business))
            ->line(__('Este es un resumen automático — no reemplaza revisar tu panel completo.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'weekly_business_report',
            'business_id' => $this->business->id,
            'business_name' => $this->business->name,
            'summary' => $this->metrics['summary'],
            'url' => route('emprendedores.negocios.metricas', $this->business),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Tu resumen semanal'), 'body' => $data['summary'], 'url' => $data['url']];
    }
}
