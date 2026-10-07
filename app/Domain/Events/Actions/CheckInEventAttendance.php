<?php

namespace App\Domain\Events\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventAttendance;
use App\Domain\Loyalty\Support\LoyaltyTokens;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Verificar la entrada de un asistente en la puerta del evento (pedido
 * del usuario: "en la zona de emprendedores debe poderse leer ese qr
 * para verificar la asistencia"). Este proyecto no tiene instalada una
 * librería de LECTURA de QR por cámara (mismo caso ya resuelto así en
 * Merkapuntos/F3.2) — el QR codifica el código en texto plano
 * (`checkin_token_encrypted`) y aquí se busca por su hash, exactamente
 * igual que `DeliverLoyaltyRedemption::findByToken()`; en la práctica,
 * un lector físico de código de barras/QR conectado como teclado llena
 * el mismo campo de texto sin necesitar cámara ni JS adicional.
 */
class CheckInEventAttendance
{
    /**
     * @throws EventActionException
     */
    public function findByToken(Business $business, string $rawToken): EventAttendance
    {
        if (! str_starts_with($rawToken, 'evt_')) {
            throw new EventActionException('Código de entrada inválido.');
        }

        $attendance = EventAttendance::with('publicEvent')
            ->where('checkin_token_hash', LoyaltyTokens::hash($rawToken))
            ->first();

        if (! $attendance || $attendance->business_id !== $business->id) {
            throw new EventActionException('Código de entrada inválido para este negocio.');
        }

        return $attendance;
    }

    /**
     * @throws EventActionException
     */
    public function handle(Business $business, User $staff, EventAttendance $attendance): EventAttendance
    {
        return DB::transaction(function () use ($business, $staff, $attendance) {
            /** @var EventAttendance $attendance */
            $attendance = EventAttendance::whereKey($attendance->id)->lockForUpdate()->firstOrFail();

            if ($attendance->business_id !== $business->id) {
                throw new EventActionException('Este código no pertenece a tu negocio.');
            }

            if ($attendance->isCheckedIn()) {
                // Idempotente: reintento tras fallo de red o doble lectura
                // del mismo QR no es un error, devuelve el mismo resultado.
                return $attendance;
            }

            if ($attendance->status !== EventAttendance::CONFIRMADA) {
                throw new EventActionException('Esta reserva no está confirmada (estado: '.$attendance->status.').');
            }

            $attendance->update([
                'checked_in_at' => now(),
                'checked_in_by_user_id' => $staff->id,
            ]);

            app(RecordAuditLog::class)->handle($staff, 'event_attendance.checked_in', $attendance, [
                'business_id' => $business->id,
                'public_event_id' => $attendance->public_event_id,
            ]);

            return $attendance;
        });
    }
}
