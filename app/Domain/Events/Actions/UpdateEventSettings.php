<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventSetting;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;

/**
 * Crea o actualiza la configuración general de eventos de un negocio
 * (TODO_desarrollo_sistema_eventos_Merkamigo.md, Fase 2 · pestaña
 * Configuración → General/Tarifas). La validación de forma (tipos,
 * mínimos) vive en el componente Livewire que llama esta acción; aquí solo
 * va la regla de negocio que no es "validación de formulario": no se
 * puede aceptar reservas privadas con pago si el negocio no tiene Wompi
 * propio conectado (Fase 2: "bloquear activación de reservas con pago si
 * faltan credenciales verificadas").
 */
class UpdateEventSettings
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Business $business, array $data, ?User $actor = null): EventSetting
    {
        if (($data['enabled'] ?? false) && config('events.pilot_mode') && ! in_array($business->id, config('events.pilot_business_ids', []), true)) {
            throw new EventActionException('Los eventos todavía están en piloto con negocios seleccionados. Pronto estarán disponibles para todos.');
        }

        if (($data['private_reservations_enabled'] ?? false) && ! $business->hasWompiConnected()) {
            throw new EventActionException('Conecta tu cuenta Wompi en "Cobros en línea" antes de aceptar reservas privadas con pago.');
        }

        if (($data['pricing_mode'] ?? EventSetting::PRICING_PLATOS) !== EventSetting::PRICING_PLATOS
            && (int) ($data['hourly_rate_cents'] ?? 0) <= 0) {
            throw new EventActionException('Define una tarifa por hora mayor que cero para la modalidad seleccionada.');
        }

        $before = (bool) $business->eventSetting()->value('private_reservations_enabled');

        $settings = $business->eventSetting()->updateOrCreate(
            ['business_id' => $business->id],
            $data,
        );

        // Fase 7: "auditar cambios sensibles" — activar/desactivar el
        // cobro de reservas privadas es el cambio de mayor impacto de
        // esta pantalla (controla si el negocio empieza a recibir dinero
        // de desconocidos a través de Merkamigo).
        if (array_key_exists('private_reservations_enabled', $data) && (bool) $data['private_reservations_enabled'] !== $before) {
            app(RecordAuditLog::class)->handle($actor, 'event_settings.private_reservations_toggled', $settings, [
                'business_id' => $business->id,
                'enabled' => (bool) $data['private_reservations_enabled'],
            ]);
        }

        return $settings;
    }
}
