<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Exceptions\IncompleteStorefrontException;
use App\Domain\Storefronts\Models\Storefront;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Publica una vitrina: valida los datos mínimos de
 * docs/product/alcance-fase0.md (nombre, categoría, municipio, WhatsApp,
 * descripción, logo, al menos un producto) y transiciona negocio + vitrina
 * a "publicado".
 */
class PublishStorefront
{
    public function handle(Business $business, User $actor): Storefront
    {
        $storefront = $business->storefront;

        $missing = $this->missingFields($business, $storefront);

        if ($missing !== []) {
            throw new IncompleteStorefrontException($missing);
        }

        return DB::transaction(function () use ($business, $storefront, $actor) {
            $business->update(['status' => 'publicado']);

            $storefront->update([
                'status' => 'publicado',
                'published_at' => $storefront->published_at ?? now(),
            ]);

            app(RecordAuditLog::class)->handle($actor, 'business.published', $business);

            return $storefront->setRelation('business', $business);
        });
    }

    /**
     * @return array<int, string>
     */
    private function missingFields(Business $business, Storefront $storefront): array
    {
        $missing = [];

        if (blank($business->name)) {
            $missing[] = 'Nombre del negocio';
        }

        if (blank($business->category_id)) {
            $missing[] = 'Categoría';
        }

        if (blank($business->municipality_id)) {
            $missing[] = 'Municipio';
        }

        if (blank($business->whatsapp_number)) {
            $missing[] = 'WhatsApp';
        }

        if (blank($storefront->description)) {
            $missing[] = 'Descripción';
        }

        if (blank($business->logo_path)) {
            $missing[] = 'Logo o foto principal';
        }

        if ($business->products()->count() === 0) {
            $missing[] = 'Al menos un producto o servicio';
        }

        return $missing;
    }
}
