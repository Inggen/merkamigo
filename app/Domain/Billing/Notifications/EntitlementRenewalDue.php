<?php

namespace App\Domain\Billing\Notifications;

use App\Domain\Billing\Models\BusinessEntitlement;
use App\Domain\Identity\Notifications\Channels\PushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * El add-on recurrente de un negocio (ej. el asistente IA) venció y no
 * tiene tarjeta guardada para renovarse solo (PR4 de
 * TODO_VENTAS_RENTABILIDAD.md, mismo patrón que
 * `SubscriptionRenewalDue`) — le queda el periodo de gracia de
 * `ProcessEntitlementRenewals` para pagar manual antes de perder el
 * acceso.
 */
class EntitlementRenewalDue extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly BusinessEntitlement $entitlement) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', PushChannel::class];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'entitlement_renewal_due',
            'business_entitlement_id' => $this->entitlement->id,
            'message' => __('Tu suscripción a :producto venció. Renuévala antes de :fecha o perderás el acceso.', [
                'producto' => $this->entitlement->sourceBillingProduct?->name ?? __('tu add-on'),
                'fecha' => $this->entitlement->grace_ends_at?->translatedFormat('d M') ?? '',
            ]),
            'url' => route('emprendedores.negocios.plan', $this->entitlement->business_id),
        ];
    }

    /**
     * @return array{title: string, body: string, url: string}
     */
    public function toPush(object $notifiable): array
    {
        $data = $this->toArray($notifiable);

        return ['title' => __('Tu suscripción está por vencer'), 'body' => $data['message'], 'url' => $data['url']];
    }
}
