<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Actions\Concerns\AuthorizesLoyaltyEmployees;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyPolicy;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Publica una nueva versión de la política de acumulación
 * (TODO_Merkapuntos.md, reglas de producto: "Política de acumulación
 * versionada, aprobada y calculada en servidor"). Archiva la versión
 * `activa` anterior — nunca hay dos políticas activas a la vez para el
 * mismo negocio, y las compras ya registradas conservan su propio snapshot
 * sin importar qué pase después aquí.
 */
class PublishLoyaltyPolicy
{
    use AuthorizesLoyaltyEmployees;

    /**
     * `type` es técnicamente opcional en la firma a propósito: este array
     * viene de un formulario (entrada externa), así que puede llegar
     * incompleto — de ahí la validación explícita justo abajo en vez de
     * confiar en el tipo.
     *
     * @param  array{type?: string, points_per_unit?: int, unit_cents?: int}  $rule
     *
     * @throws LoyaltyActionException
     */
    public function handle(Business $business, User $actor, array $rule, ?string $eligibleBaseNotes = null): LoyaltyPolicy
    {
        $this->assertBusinessOwner($business, $actor, 'Solo el dueño del negocio puede publicar la política de acumulación.');

        if (($rule['type'] ?? null) !== 'simple') {
            throw new LoyaltyActionException('Por ahora solo se admite la regla "simple" (puntos fijos por cada tramo de gasto).');
        }

        if ((int) ($rule['points_per_unit'] ?? 0) <= 0 || (int) ($rule['unit_cents'] ?? 0) <= 0) {
            throw new LoyaltyActionException('La regla necesita puntos por unidad y un valor de unidad mayores que cero.');
        }

        return DB::transaction(function () use ($business, $actor, $rule, $eligibleBaseNotes) {
            $business->loyaltyPolicies()
                ->where('status', LoyaltyPolicy::ACTIVA)
                ->update(['status' => LoyaltyPolicy::ARCHIVADA, 'effective_until' => now()]);

            $nextVersion = ((int) $business->loyaltyPolicies()->max('version')) + 1;

            $policy = $business->loyaltyPolicies()->create([
                'version' => $nextVersion,
                'status' => LoyaltyPolicy::ACTIVA,
                'rule' => $rule,
                'eligible_base_notes' => $eligibleBaseNotes,
                'effective_from' => now(),
                'created_by_user_id' => $actor->id,
            ]);

            app(RecordAuditLog::class)->handle($actor, 'loyalty.policy.published', $policy, [
                'business_id' => $business->id,
                'version' => $nextVersion,
            ]);

            return $policy;
        });
    }
}
