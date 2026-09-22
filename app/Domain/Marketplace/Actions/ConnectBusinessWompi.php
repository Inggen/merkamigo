<?php

namespace App\Domain\Marketplace\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Marketplace\Models\BusinessWompiCredential;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use App\Support\Wompi\BusinessWompiClient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Guarda las credenciales de Wompi DEL NEGOCIO (nunca las de Merkamigo).
 * Verifica la llave pública contra la API de Wompi antes de guardar —
 * evita que un typo quede sin detectar hasta que un cliente real intente
 * pagar.
 */
class ConnectBusinessWompi
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Business $business, array $data, User $actor): BusinessWompiCredential
    {
        // Candado de confianza (pendiente #2 de TODO-Marketplace-Checkout.md,
        // resuelto en esta sesión): antes de dejar que un negocio reciba
        // dinero real de un cliente, exige la misma verificación de
        // identidad que ya existe para el sello de confianza — evita que
        // cualquiera abra una vitrina falsa a cobrar con pasarela real.
        if (! $business->hasVerifiedBadge()) {
            throw ValidationException::withMessages([
                'public_key' => 'Tu negocio necesita completar la verificación de identidad antes de conectar pagos en línea.',
            ]);
        }

        $validated = Validator::make($data, [
            'public_key' => ['required', 'string', 'starts_with:pub_'],
            'private_key' => ['required', 'string', 'starts_with:prv_'],
            'integrity_secret' => ['required', 'string'],
            'events_secret' => ['required', 'string'],
            'environment' => ['required', 'in:sandbox,production'],
        ])->validate();

        $credential = $business->wompiCredential ?? new BusinessWompiCredential(['business_id' => $business->id]);
        $credential->fill($validated);

        $client = new BusinessWompiClient($credential);

        if (! $client->fetchMerchant()) {
            throw ValidationException::withMessages([
                'public_key' => 'No pudimos verificar esa llave pública con Wompi. Revisa que sea correcta y del mismo ambiente (sandbox/producción) que elegiste.',
            ]);
        }

        $credential->is_active = true;
        $credential->connected_at = now();
        $credential->save();

        app(RecordAuditLog::class)->handle($actor, 'business.wompi_connected', $business, [
            'environment' => $credential->environment,
        ]);

        return $credential;
    }
}
