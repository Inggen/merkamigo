<?php

namespace Tests\Feature\Events;

use App\Domain\Events\Actions\QuoteEventReservation;
use App\Domain\Events\Exceptions\EventActionException;
use App\Domain\Events\Models\EventBusinessEquipment;
use App\Domain\Events\Models\EventDish;
use App\Domain\Events\Models\EventSetting;
use Tests\TestCase;

/**
 * Fórmulas del cotizador (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 3): `platos = Σ(cantidad × precio)`; `lugar = duración × precio/hora`;
 * `equipos = Σ(cantidad × cargo)`. Caso de referencia del propio
 * documento: "2 crepes + 3 sándwiches + 1 estroganoff = 6 platos para
 * 6 pax", 2 horas de duración, con sonido adicional.
 */
class EventReservationQuoteTest extends TestCase
{
    private function dishes(): array
    {
        return [
            ['dish' => new EventDish(['name' => 'Crepes', 'price_cents' => 2500000]), 'quantity' => 2],
            ['dish' => new EventDish(['name' => 'Sándwiches', 'price_cents' => 3000000]), 'quantity' => 3],
            ['dish' => new EventDish(['name' => 'Estroganoff', 'price_cents' => 3500000]), 'quantity' => 1],
        ];
    }

    private function equipment(): array
    {
        return [
            ['equipment' => new EventBusinessEquipment(['custom_name' => 'Sonido', 'fee_cents' => 3000000]), 'quantity' => 1],
        ];
    }

    public function test_dish_pricing_mode_only_charges_the_dishes(): void
    {
        $settings = new EventSetting(['pricing_mode' => EventSetting::PRICING_PLATOS]);

        $quote = app(QuoteEventReservation::class)->handle($settings, 2, $this->dishes(), $this->equipment());

        $this->assertSame(0, $quote['space_total_cents']);
        $this->assertSame(17500000, $quote['dishes_total_cents']);
        $this->assertSame(3000000, $quote['equipment_total_cents']);
        $this->assertSame(20500000, $quote['total_cents']);
    }

    public function test_hourly_pricing_mode_only_charges_the_space(): void
    {
        $settings = new EventSetting(['pricing_mode' => EventSetting::PRICING_HORAS, 'hourly_rate_cents' => 8000000]);

        $quote = app(QuoteEventReservation::class)->handle($settings, 2, $this->dishes(), $this->equipment());

        $this->assertSame(16000000, $quote['space_total_cents']);
        $this->assertSame(0, $quote['dishes_total_cents']);
        $this->assertSame(3000000, $quote['equipment_total_cents']);
        $this->assertSame(19000000, $quote['total_cents']);
    }

    public function test_hybrid_pricing_mode_charges_both_space_and_dishes(): void
    {
        $settings = new EventSetting(['pricing_mode' => EventSetting::PRICING_HIBRIDO, 'hourly_rate_cents' => 8000000]);

        $quote = app(QuoteEventReservation::class)->handle($settings, 2, $this->dishes(), $this->equipment());

        $this->assertSame(16000000, $quote['space_total_cents']);
        $this->assertSame(17500000, $quote['dishes_total_cents']);
        $this->assertSame(3000000, $quote['equipment_total_cents']);
        $this->assertSame(36500000, $quote['total_cents']);
    }

    public function test_hourly_and_hybrid_modes_require_a_configured_hourly_rate(): void
    {
        $settings = new EventSetting(['pricing_mode' => EventSetting::PRICING_HORAS, 'hourly_rate_cents' => null]);

        $this->expectException(EventActionException::class);

        app(QuoteEventReservation::class)->handle($settings, 2, [], []);
    }

    public function test_equipment_with_no_fee_is_free_but_still_selectable(): void
    {
        $settings = new EventSetting(['pricing_mode' => EventSetting::PRICING_PLATOS]);
        $equipment = [['equipment' => new EventBusinessEquipment(['custom_name' => 'Decoración', 'fee_cents' => null]), 'quantity' => 1]];

        $quote = app(QuoteEventReservation::class)->handle($settings, 1, [], $equipment);

        $this->assertSame(0, $quote['equipment_total_cents']);
        $this->assertSame(0, $quote['total_cents']);
    }
}
