<?php

namespace Tests\Feature\Storefronts;

use App\Domain\Billing\Actions\SubscribeToPlan;
use App\Domain\Billing\Models\Plan;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateProduct;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Storefronts\Actions\PublishStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 1.6 del TODO: Inicio del Emprendedor con guía "qué te falta para vender"
 * y accesos grandes a vitrina, productos, WhatsApp y QR.
 */
class EmprendedorHomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_draft_business_shows_what_is_missing_to_sell(): void
    {
        $owner = User::factory()->create();
        app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio a medias']);

        $this->actingAs($owner)
            ->get(route('emprendedores.home'))
            ->assertOk()
            ->assertSee(__('Te falta para vender:'))
            ->assertSee('Categoría')
            ->assertSee('WhatsApp');
    }

    public function test_a_published_business_shows_whatsapp_and_share_quick_actions_without_the_missing_guide(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'is_active' => true]);

        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Completo',
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
            'description' => 'Todo listo.',
        ])->business;
        $business->update(['logo_path' => 'businesses/1/logo.jpg']);
        app(CreateProduct::class)->handle($business, [
            'name' => 'Producto', 'type' => 'producto', 'price_type' => 'consultar',
        ], [], $owner);
        app(PublishStorefront::class)->handle($business, $owner);

        $response = $this->actingAs($owner)->get(route('emprendedores.home'));

        $response->assertOk();
        $response->assertDontSee(__('Te falta para vender:'));
        $response->assertSee(__('WhatsApp'));
        $response->assertSee(__('Compartir y QR'));
    }

    public function test_the_sidebar_offers_a_persistent_help_link(): void
    {
        $owner = User::factory()->create(['experience' => 'emprendedor']);
        app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio a medias']);

        $this->actingAs($owner)
            ->get(route('emprendedores.home'))
            ->assertOk()
            ->assertSee(__('Ayuda'))
            ->assertSeeHtml(route('soporte'));
    }

    public function test_free_plan_hides_the_create_another_storefront_action_after_reaching_the_limit(): void
    {
        $owner = User::factory()->create(['experience' => 'emprendedor']);
        app(CreateStorefront::class)->handle($owner, ['name' => 'Única Vitrina']);

        $this->actingAs($owner)
            ->get(route('emprendedores.home'))
            ->assertOk()
            ->assertDontSee(__('+ Crear otra vitrina'))
            ->assertSee(__('Ya alcanzaste el máximo de 1 vitrinas para tu plan actual.'));
    }

    public function test_emprendedor_plan_keeps_the_create_action_visible_until_the_third_storefront(): void
    {
        $owner = User::factory()->create(['experience' => 'emprendedor']);
        $firstBusiness = app(CreateStorefront::class)->handle($owner, ['name' => 'Primera Vitrina'])->business;

        $plan = Plan::create([
            'slug' => 'emprendedor',
            'name' => 'Emprendedor',
            'description' => 'Más productos, colaboradores y destacados.',
            'price_cents' => 1990000,
            'billing_period' => Plan::MENSUAL,
            'limits' => ['max_products' => null, 'max_members' => 5, 'max_featured_days' => 7, 'max_storefronts' => 3],
            'trial_days' => 14,
            'is_active' => true,
            'position' => 1,
        ]);

        app(SubscribeToPlan::class)->handle($firstBusiness, $plan, $owner);
        app(CreateStorefront::class)->handle($owner, ['name' => 'Segunda Vitrina']);

        $this->actingAs($owner)
            ->get(route('emprendedores.home'))
            ->assertOk()
            ->assertSee(__('+ Crear otra vitrina'));

        app(CreateStorefront::class)->handle($owner, ['name' => 'Tercera Vitrina']);

        $this->actingAs($owner)
            ->get(route('emprendedores.home'))
            ->assertOk()
            ->assertDontSee(__('+ Crear otra vitrina'))
            ->assertSee(__('Ya alcanzaste el máximo de 3 vitrinas para tu plan actual.'));
    }
}
