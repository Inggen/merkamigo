<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Actions\Concerns\AuthorizesLoyaltyEmployees;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyAccount;
use App\Domain\Loyalty\Models\LoyaltyMovement;
use App\Domain\Loyalty\Models\LoyaltyPolicy;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Loyalty\Notifications\PointsAccrued;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Registrar compra presencial (TODO_Merkapuntos.md, F1.3/F1.4). Único
 * punto de entrada para acreditar puntos por una compra — nunca se crea un
 * `LoyaltyMovement` de acumulación por fuera de aquí.
 */
class RegisterLoyaltyPurchase
{
    use AuthorizesLoyaltyEmployees;

    /**
     * @throws LoyaltyActionException
     */
    public function handle(
        Business $business,
        User $employee,
        User $customer,
        int $eligibleAmountCents,
        string $idempotencyKey,
        ?string $externalReference = null,
        string $origin = LoyaltyPurchase::PRESENCIAL,
    ): LoyaltyPurchase {
        // F1.3, aceptación: "doble clic o pérdida de conexión crea una
        // compra y una acreditación" — un reintento con la misma clave
        // devuelve la compra ya creada, sin volver a acreditar.
        $existing = LoyaltyPurchase::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return $existing;
        }

        $this->assertAuthorizedEmployee($business, $employee);

        if ($eligibleAmountCents <= 0) {
            throw new LoyaltyActionException('El valor de la compra debe ser mayor que cero.');
        }

        $enrollment = $business->loyaltyEnrollment;

        if (! $enrollment || ! $enrollment->isActive()) {
            throw new LoyaltyActionException('Este negocio no tiene Merkamigo Premia activo.');
        }

        $policy = $business->loyaltyPolicies()->where('status', LoyaltyPolicy::ACTIVA)->first();

        if (! $policy) {
            throw new LoyaltyActionException('Este negocio no tiene una política de acumulación activa.');
        }

        // F1.4: "Recibo externo único por negocio cuando existe" — una
        // referencia repetida no acredita de nuevo, se informa con el dato
        // de la compra ya existente en vez de fallar en silencio.
        if ($externalReference !== null) {
            $duplicate = LoyaltyPurchase::where('business_id', $business->id)
                ->where('external_reference', $externalReference)
                ->first();

            if ($duplicate) {
                throw new LoyaltyActionException("Esta referencia ya fue registrada el {$duplicate->created_at->format('d/m/Y H:i')} ({$duplicate->points_awarded} puntos).");
            }
        }

        $points = $policy->pointsFor($eligibleAmountCents);

        // F1.4: "Añadir aviso por coincidencia cliente/valor/ventana
        // temporal y revisión, sin bloquear" — heurística de alerta, no de
        // bloqueo. Los casos sin recibo no tienen forma de verificarse
        // mejor que esto; el límite queda documentado, no oculto.
        $possibleDuplicate = LoyaltyPurchase::where('business_id', $business->id)
            ->where('customer_user_id', $customer->id)
            ->where('eligible_amount_cents', $eligibleAmountCents)
            ->where('status', LoyaltyPurchase::REGISTRADA)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        $purchase = DB::transaction(function () use (
            $business, $employee, $customer, $eligibleAmountCents, $idempotencyKey,
            $externalReference, $origin, $policy, $points, $possibleDuplicate,
        ) {
            $account = LoyaltyAccount::firstOrCreate(
                ['business_id' => $business->id, 'user_id' => $customer->id],
                ['status' => LoyaltyAccount::ACTIVA],
            );

            $purchase = LoyaltyPurchase::create([
                'business_id' => $business->id,
                'customer_user_id' => $customer->id,
                'eligible_amount_cents' => $eligibleAmountCents,
                'origin' => $origin,
                'external_reference' => $externalReference,
                'status' => LoyaltyPurchase::REGISTRADA,
                'policy_id' => $policy->id,
                'policy_snapshot' => [
                    'version' => $policy->version,
                    'rule' => $policy->rule,
                    'eligible_base_notes' => $policy->eligible_base_notes,
                ],
                'points_awarded' => $points,
                'employee_user_id' => $employee->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            LoyaltyMovement::create([
                'account_id' => $account->id,
                'type' => LoyaltyMovement::ACUMULACION,
                'points' => $points,
                'reference_type' => $purchase->getMorphClass(),
                'reference_id' => $purchase->id,
                'idempotency_key' => "movement_{$idempotencyKey}",
                'actor_user_id' => $employee->id,
                'metadata' => $possibleDuplicate ? ['possible_duplicate' => true] : null,
            ]);

            app(RecordAuditLog::class)->handle($employee, 'loyalty.purchase.registered', $purchase, [
                'business_id' => $business->id,
                'customer_user_id' => $customer->id,
                'points_awarded' => $points,
                'possible_duplicate' => $possibleDuplicate,
            ]);

            return $purchase;
        });

        // F1.3, aceptación: "notificación posterior a commit" — fuera de
        // la transacción a propósito.
        $customer->notify(new PointsAccrued($purchase));

        return $purchase;
    }
}
