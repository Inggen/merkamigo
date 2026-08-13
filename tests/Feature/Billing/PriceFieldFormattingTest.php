<?php

namespace Tests\Feature\Billing;

use App\Domain\Billing\Models\BillingProduct;
use App\Domain\Billing\Models\Plan;
use App\Filament\Resources\BillingProducts\Pages\CreateBillingProduct;
use App\Filament\Resources\BillingProducts\Pages\EditBillingProduct;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * El admin escribe/ve el precio en pesos (ej. 29.900); `price_cents` sigue
 * guardando centavos porque Wompi exige `amount_in_cents` en su API —
 * `formatStateUsing`/`dehydrateStateUsing` hacen esa conversión para que
 * nadie tenga que calcular "x100" a mano (ver BillingProductForm/PlanForm).
 */
class PriceFieldFormattingTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    public function test_the_billing_product_edit_form_shows_the_price_in_pesos(): void
    {
        $product = BillingProduct::create([
            'slug' => 'destacado-30', 'name' => 'Destacado 30 días',
            'price_cents' => 2990000, 'kind' => BillingProduct::DESTACADO,
            'payload' => ['days' => 30], 'is_active' => true,
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(EditBillingProduct::class, ['record' => $product->getRouteKey()])
            ->assertSet('data.price_cents', 29900);
    }

    public function test_creating_a_billing_product_with_a_price_in_pesos_stores_it_in_cents(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(CreateBillingProduct::class)
            ->fillForm([
                'name' => 'Asistente IA para tu vitrina',
                'slug' => 'asistente-ia',
                'price_cents' => 49900,
                'kind' => BillingProduct::ENTITLEMENT,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('billing_products', [
            'slug' => 'asistente-ia',
            'price_cents' => 4990000,
        ]);
    }

    public function test_the_plan_edit_form_shows_the_price_in_pesos_and_blank_stays_free(): void
    {
        $paidPlan = Plan::create([
            'slug' => 'emprendedor', 'name' => 'Emprendedor', 'price_cents' => 1990000,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 1,
        ]);
        $freePlan = Plan::create([
            'slug' => 'gratis-plus', 'name' => 'Gratis Plus', 'price_cents' => null,
            'billing_period' => Plan::MENSUAL, 'is_active' => true, 'position' => 0,
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(EditPlan::class, ['record' => $paidPlan->getRouteKey()])
            ->assertSet('data.price_cents', 19900);

        Livewire::test(EditPlan::class, ['record' => $freePlan->getRouteKey()])
            ->assertSet('data.price_cents', null)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($freePlan->fresh()->price_cents);
    }
}
