<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\IssueLoyaltyIdentityToken;
use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Domain\Loyalty\Actions\ReserveLoyaltyRedemption;
use App\Domain\Loyalty\Actions\SubmitLoyaltyReceiptClaim;
use App\Domain\Loyalty\Models\LoyaltyEnrollment;
use App\Domain\Loyalty\Models\LoyaltyPurchase;
use App\Domain\Loyalty\Models\LoyaltyRedemption;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, Fase 3 — panel del negocio: adhesión, premios,
 * escáner único y resultados.
 */
class MerkapuntosBusinessPanelTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    private function freshBusiness(): array
    {
        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Negocio Panel '.uniqid(),
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        return [$business->fresh(), $owner];
    }

    public function test_the_owner_can_enroll_publish_a_policy_and_activate_the_program(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('consentAccepted', true)
            ->set('budgetCop', 150000)
            ->call('enroll')
            ->assertHasNoErrors();

        $this->assertSame(LoyaltyEnrollment::PENDIENTE, $business->loyaltyEnrollment->status);

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('pointsPerUnit', 1)
            ->set('unitCop', 1000)
            ->call('publishPolicy')
            ->call('activateProgram')
            ->assertHasNoErrors();

        $this->assertTrue($business->loyaltyEnrollment->fresh()->isActive());
    }

    public function test_a_collaborator_cannot_enroll_the_business(): void
    {
        [$business, $owner] = $this->freshBusiness();
        $collaborator = $this->addEmployee($business, 'collaborator');

        Livewire::actingAs($collaborator)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('consentAccepted', true)
            ->call('enroll');

        $this->assertNull($business->loyaltyEnrollment()->first());
    }

    public function test_the_scanner_identifies_a_customer_code_and_registers_a_purchase(): void
    {
        // unitCents por defecto = 100.000 (= $1.000 COP): el escáner
        // recibe el valor en COP y lo convierte ×100 antes de llamar a la
        // acción, así que la política también debe pensarse en COP.
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $token = app(IssueLoyaltyIdentityToken::class)->handle($customer);

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('activeTab', 'escaner')
            ->assertSee('Tomar foto del QR')
            ->assertSee('Elegir imagen del QR')
            ->assertSee('O usa el código manual')
            ->set('scanInput', $token)
            ->call('scan')
            ->assertSet('scanResult.type', 'purchase')
            ->assertSet('scanResult.customer_id', $customer->id)
            ->set('purchaseAmountCop', 40000)
            ->call('confirmPurchase');

        $purchase = LoyaltyPurchase::firstOrFail();
        $this->assertSame(40, $purchase->points_awarded);
        $this->assertSame($customer->id, $purchase->customer_user_id);
        $this->assertSame($owner->id, $purchase->employee_user_id);

        $component->assertSet('scanResult', null);
        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::LOYALTY_PURCHASE_CONFIRMED)->count());
        $this->assertSame(0, AnalyticsEvent::where('type', AnalyticsEvent::LOYALTY_REPURCHASE)->count());
    }

    public function test_a_second_purchase_from_the_same_customer_is_recorded_as_a_repurchase(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        $token = app(IssueLoyaltyIdentityToken::class)->handle($customer);

        $scan = fn () => Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('scanInput', $token)
            ->call('scan')
            ->set('purchaseAmountCop', 10000)
            ->call('confirmPurchase');

        $scan();
        $scan();

        $this->assertSame(2, AnalyticsEvent::where('type', AnalyticsEvent::LOYALTY_PURCHASE_CONFIRMED)->count());
        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::LOYALTY_REPURCHASE)->count());
    }

    public function test_the_scanner_identifies_a_redemption_code_and_delivers_it(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 1_000);
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 100_000, Str::uuid()->toString());

        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café gratis', 'points_cost' => 50, 'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('scanInput', $result['token'])
            ->call('scan')
            ->assertSet('scanResult.type', 'delivery')
            ->call('confirmDelivery');

        $this->assertSame(LoyaltyRedemption::ENTREGADO, $result['redemption']->fresh()->status);
    }

    public function test_the_scanner_rejects_a_redemption_token_from_another_business(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        [$otherBusiness, $otherOwner] = $this->enrolledBusiness();
        $customer = User::factory()->create();
        app(RegisterLoyaltyPurchase::class)->handle($otherBusiness, $otherOwner, $customer, 2_000_000, Str::uuid()->toString());

        $reward = app(CreateLoyaltyReward::class)->handle($otherBusiness, $otherOwner, [
            'title' => 'Café gratis', 'points_cost' => 10, 'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $otherOwner);
        $result = app(ReserveLoyaltyRedemption::class)->handle($customer, $reward, Str::uuid()->toString());

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('scanInput', $result['token'])
            ->call('scan')
            ->assertSet('scanResult', null);
    }

    public function test_creating_publishing_and_pausing_a_reward_from_the_panel(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $product = $business->products()->create([
            'name' => 'Café de la casa',
            'slug' => 'cafe-de-la-casa',
            'price_type' => 'sin_precio',
        ]);

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('rewardProductId', $product->id)
            ->set('rewardTitle', 'Café de la casa')
            ->set('rewardPointsCost', 400)
            ->set('rewardFullCostCop', 8000)
            ->call('createReward')
            ->assertHasNoErrors();

        $reward = $business->loyaltyRewards()->firstOrFail();
        $this->assertSame('borrador', $reward->status);
        $this->assertSame(800000, $reward->full_cost_cents);

        $component->call('publishReward', $reward->id);
        $this->assertSame('publicado', $reward->fresh()->status);

        $component->call('pauseReward', $reward->id);
        $this->assertSame('pausado', $reward->fresh()->status);
    }

    public function test_the_owner_can_associate_a_product_upload_a_promotion_image_and_edit_the_reward(): void
    {
        Storage::fake('public');
        [$business, $owner] = $this->enrolledBusiness();
        $product = $business->products()->create([
            'name' => 'Cheesecake artesanal',
            'slug' => 'cheesecake-artesanal',
            'price_type' => 'sin_precio',
        ]);

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('rewardProductId', $product->id)
            ->set('rewardTitle', 'Cheesecake de premio')
            ->set('rewardPointsCost', 300)
            ->set('rewardFullCostCop', 12000)
            ->set('rewardImage', UploadedFile::fake()->image('promocion.jpg', 1200, 800))
            ->call('saveReward')
            ->assertHasNoErrors();

        $reward = $business->loyaltyRewards()->firstOrFail();
        $this->assertSame($product->id, $reward->product_id);
        $this->assertNotNull($reward->image_path);
        Storage::disk('public')->assertExists($reward->image_path);

        $component
            ->call('editReward', $reward->id)
            ->assertSet('rewardProductId', $product->id)
            ->assertSet('rewardTitle', 'Cheesecake de premio')
            ->set('rewardTitle', 'Cheesecake para compartir')
            ->call('saveReward')
            ->assertHasNoErrors();

        $this->assertSame('Cheesecake para compartir', $reward->fresh()->title);
        $this->assertSame(2, $reward->fresh()->version);
    }

    public function test_the_average_ticket_is_calculated_automatically_from_purchase_history_with_no_manual_input(): void
    {
        // Pedido del usuario: "Dejalo automático porque así es muy
        // difícil para el emprendedor" — el comerciante no escribe nada,
        // el ticket promedio sale solo del historial real de compras.
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);
        $customer = User::factory()->create();

        // Tres compras ($15.000, $20.000, $25.000) → promedio $20.000.
        foreach ([1_500_000, 2_000_000, 2_500_000] as $i => $amountCents) {
            app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, $amountCents, "ticket-promedio-{$i}");
        }

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->assertSet('averageTicketCents', 2_000_000)
            ->set('rewardFullCostCop', 8000)
            ->assertSet('rewardAverageTicketCop', null)
            ->assertSet('overrideAverageTicket', false)
            ->assertSet('rewardSuggestion.recommendations.0.purchases', 4)
            ->assertSet('rewardSuggestion.recommendations.0.points', 80)
            ->assertSet('rewardSuggestion.recommendations.1.purchases', 6)
            ->assertSet('rewardSuggestion.recommendations.1.points', 120)
            ->assertSet('rewardSuggestion.recommendations.2.purchases', 8)
            ->assertSet('rewardSuggestion.recommendations.2.points', 160)
            ->call('useSuggestion', 120)
            ->assertSet('rewardPointsCost', 120)
            // "Valor manual": con los puntos ya elegidos, recalcula en
            // tiempo real gasto/compras/porcentaje — usando también el
            // ticket promedio automático.
            ->assertSet('manualPointsEstimate.required_spend_cents', 12_000_000)
            ->assertSet('manualPointsEstimate.estimated_purchases', 6.0);
    }

    public function test_a_brand_new_business_without_enough_purchase_history_falls_back_to_a_manual_estimate(): void
    {
        // Sin historial suficiente (menos de 3 compras), no se puede
        // calcular solo — ahí, y solo ahí, se le pide un estimado.
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->assertSet('averageTicketCents', null)
            ->set('rewardFullCostCop', 8000)
            ->assertSet('rewardSuggestion', null)
            ->set('rewardAverageTicketCop', 20000)
            ->assertSet('rewardSuggestion.recommendations.1.points', 120);
    }

    public function test_the_owner_can_override_the_automatic_average_ticket(): void
    {
        [$business, $owner] = $this->enrolledBusiness(pointsPerUnit: 1, unitCents: 100_000);
        $customer = User::factory()->create();

        foreach ([1_500_000, 2_000_000, 2_500_000] as $i => $amountCents) {
            app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, $amountCents, "override-{$i}");
        }

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('rewardFullCostCop', 8000)
            ->assertSet('rewardSuggestion.recommendations.1.points', 120) // con el automático de $20.000.
            ->call('toggleAverageTicketOverride')
            ->assertSet('overrideAverageTicket', true)
            ->set('rewardAverageTicketCop', 10000)
            ->assertSet('rewardSuggestion.recommendations.1.points', 60) // 6 × $10.000 = $60.000 → 60 puntos.
            ->call('toggleAverageTicketOverride')
            ->assertSet('overrideAverageTicket', false)
            ->assertSet('rewardAverageTicketCop', null)
            ->assertSet('rewardSuggestion.recommendations.1.points', 120); // vuelve al automático.
    }

    public function test_no_fraction_fields_remain_in_the_reward_modal(): void
    {
        // TODO_Correccion_Logica_Merkapuntos.md: "Eliminar estos campos
        // de la interfaz visible para el comerciante": Fracción mínima/máxima.
        [$business, $owner] = $this->enrolledBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->set('activeTab', 'premios')
            ->call('startNewReward')
            ->assertDontSee('Fracción mín')
            ->assertDontSee('Fracción máx')
            ->assertSee('Ticket promedio');
    }

    public function test_approving_a_receipt_claim_from_the_panel_accredits_points(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        Storage::fake('private');
        $claim = app(SubmitLoyaltyReceiptClaim::class)->handle($business, $customer, UploadedFile::fake()->image('r.jpg'));

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.merkapuntos', ['business' => $business])
            ->call('startReviewingClaim', $claim->id)
            ->set('reviewAmountCop', 50000)
            ->call('approveClaim');

        $this->assertSame('aprobada', $claim->fresh()->status);
        $this->assertSame(50, LoyaltyPurchase::firstOrFail()->points_awarded);
    }

    public function test_the_receipt_document_endpoint_is_scoped_to_the_business(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        [$otherBusiness, $otherOwner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        Storage::fake('private');
        $claim = app(SubmitLoyaltyReceiptClaim::class)->handle($business, $customer, UploadedFile::fake()->image('r.jpg'));

        // El negocio de la URL no coincide con el dueño real de la
        // solicitud — 404 en vez de 403, para no filtrar que la
        // solicitud sí existe (solo para otro negocio).
        $this->actingAs($otherOwner)
            ->get(route('emprendedores.negocios.merkapuntos.solicitudes.recibo', [$otherBusiness, $claim]))
            ->assertNotFound();

        // Con el negocio correcto en la URL, sí se evalúa la autorización
        // real — y falla porque `$otherOwner` no pertenece a ese negocio.
        $this->actingAs($otherOwner)
            ->get(route('emprendedores.negocios.merkapuntos.solicitudes.recibo', [$business, $claim]))
            ->assertForbidden();
    }
}
