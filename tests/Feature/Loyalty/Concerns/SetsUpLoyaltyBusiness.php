<?php

namespace Tests\Feature\Loyalty\Concerns;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Loyalty\Actions\EnrollBusinessInLoyalty;
use App\Domain\Loyalty\Actions\PublishLoyaltyPolicy;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait SetsUpLoyaltyBusiness
{
    /**
     * @return array{0: Business, 1: User} [$business, $owner] — negocio
     *                                     publicado, adherido y con una política activa de 1 punto
     *                                     por cada 1.000 COP (100.000 centavos) elegibles.
     */
    private function enrolledBusiness(int $pointsPerUnit = 1, int $unitCents = 100000): array
    {
        $municipality = Municipality::firstOrCreate(
            ['slug' => 'cajica'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'alimentos'],
            ['name' => 'Alimentos', 'is_active' => true],
        );

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Merkapuntos '.uniqid(),
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        $enrollment = app(EnrollBusinessInLoyalty::class)->handle($business, $owner, 'v1', 50_000_00);
        app(PublishLoyaltyPolicy::class)->handle($business, $owner, [
            'type' => 'simple',
            'points_per_unit' => $pointsPerUnit,
            'unit_cents' => $unitCents,
        ]);
        app(EnrollBusinessInLoyalty::class)->activate($enrollment, $owner);

        return [$business->fresh(), $owner];
    }

    private function addEmployee(Business $business, string $role = 'collaborator'): User
    {
        $employee = User::factory()->create();

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($business->id);
        $employee->assignRole(Role::findOrCreate($role, 'web'));
        setPermissionsTeamId($previousTeamId);

        return $employee;
    }
}
