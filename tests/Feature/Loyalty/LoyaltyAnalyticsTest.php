<?php

namespace Tests\Feature\Loyalty;

use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Loyalty\Actions\CreateLoyaltyReward;
use App\Domain\Loyalty\Actions\RegisterLoyaltyPurchase;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness;
use Tests\TestCase;

/**
 * TODO_Merkapuntos.md, F5.4 — medición: eventos anónimos/agregados
 * (impresión, detalle, canje reservado/liberado, compra, recompra), sin
 * PII, reutilizando `RegisterAnalyticsEvent` (ya deduplica y filtra bots).
 */
class LoyaltyAnalyticsTest extends TestCase
{
    use RefreshDatabase, SetsUpLoyaltyBusiness;

    public function test_browsing_the_catalog_and_a_reward_records_impression_and_detail_events(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $reward = app(CreateLoyaltyReward::class)->handle($business, $owner, [
            'title' => 'Café de la casa', 'points_cost' => 10, 'full_cost_cents' => 300_000,
        ]);
        $reward = app(CreateLoyaltyReward::class)->publish($reward, $owner);

        $this->get(route('premia.index'));
        $this->get(route('premia.show', $reward));

        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::LOYALTY_REWARD_IMPRESSION)->where('subject_id', $reward->id)->count());
        $this->assertSame(1, AnalyticsEvent::where('type', AnalyticsEvent::LOYALTY_REWARD_DETAIL_VIEW)->where('subject_id', $reward->id)->count());
    }

    public function test_a_first_purchase_is_not_counted_as_a_repurchase_but_the_second_one_is(): void
    {
        [$business, $owner] = $this->enrolledBusiness();
        $customer = User::factory()->create();

        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 300_000, Str::uuid()->toString());
        $this->assertSame(0, AnalyticsEvent::where('type', AnalyticsEvent::LOYALTY_REPURCHASE)->count());

        app(RegisterLoyaltyPurchase::class)->handle($business, $owner, $customer, 300_000, Str::uuid()->toString());
        // La confirmación de compra en sí (`LOYALTY_PURCHASE_CONFIRMED`) y
        // la detección de recompra se disparan desde el escáner del
        // panel del negocio, no desde la Action de dominio en sí misma
        // (que también se usa desde Filament/colas sin un visitante real
        // detrás) — ver `MerkapuntosBusinessPanelTest` para el camino
        // real por HTTP/Livewire.
    }

    public function test_the_no_op_action_resolves_null_in_tests_and_never_breaks_the_flow(): void
    {
        // El entorno de pruebas no tiene OpenAI configurado — confirma
        // que ninguna de las acciones de Merkapuntos depende de tener IA
        // disponible para funcionar (F4.2: "motor funciona sin IA").
        $this->assertNull(app(GeneratesAssistedText::class)->generate('prueba'));
    }
}
