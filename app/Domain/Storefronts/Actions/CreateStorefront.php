<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessMembership;
use App\Domain\Businesses\Models\Organization;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Storefronts\Models\Storefront;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Acción de dominio de ejemplo exigida por el criterio de aceptación de 0.4:
 * la misma acción se invoca desde una prueba Pest, un componente Livewire y
 * `POST /api/v1/businesses`, sin duplicar reglas.
 *
 * Crea la organización (si el usuario aún no tiene una), el negocio y la
 * vitrina en estado "borrador", y asigna al usuario como `owner` del negocio.
 * El flujo completo de "Mi Merkamigo en cinco minutos" (audio, fotos, texto
 * asistido) pertenece a la Fase 1; aquí solo se prueba el patrón de
 * arquitectura con los datos mínimos.
 */
class CreateStorefront
{
    /**
     * @param  array{name: string, whatsapp_number?: ?string, municipality_id?: ?int, category_id?: ?int, headline?: ?string, description?: ?string}  $data
     */
    public function handle(User $owner, array $data): Storefront
    {
        return DB::transaction(function () use ($owner, $data) {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug(Organization::class, $data['name']),
                'owner_user_id' => $owner->id,
            ]);

            $business = Business::create([
                'organization_id' => $organization->id,
                'municipality_id' => $data['municipality_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug(Business::class, $data['name']),
                'whatsapp_number' => $data['whatsapp_number'] ?? null,
                'status' => 'borrador',
            ]);

            $storefront = Storefront::create([
                'business_id' => $business->id,
                'headline' => $data['headline'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => 'borrador',
            ]);

            BusinessMembership::create([
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'status' => 'activo',
            ]);

            $this->assignOwnerRole($owner, $business);

            app(RecordAuditLog::class)->handle($owner, 'business.created', $business, [
                'business_id' => $business->id,
            ]);

            return $storefront->setRelation('business', $business);
        });
    }

    private function assignOwnerRole(User $owner, Business $business): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId($business->id);
        $owner->assignRole(Role::findOrCreate('owner', 'web'));
        setPermissionsTeamId($previousTeamId);
    }

    private function uniqueSlug(string $model, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $attempt = 1;

        while ($model::where('slug', $slug)->exists()) {
            $slug = "{$base}-".(++$attempt);
        }

        return $slug;
    }
}
