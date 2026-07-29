<?php

namespace Tests\Feature\Businesses;

use App\Domain\Businesses\Models\Business;
use App\Domain\Businesses\Models\BusinessAttribute;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 1.3 del TODO: horario estructurado por día y "Abierto ahora"/"Cerrado"
 * calculado, y atributos administrables.
 */
class BusinessScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function business(): Business
    {
        $owner = User::factory()->create();

        return app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Horario'])->business;
    }

    public function test_is_open_now_is_null_without_a_structured_schedule(): void
    {
        $business = $this->business();

        $this->assertNull($business->isOpenNow());
    }

    public function test_is_open_now_is_false_when_todays_entry_is_marked_closed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 10:00:00')); // lunes
        $today = strtolower(now()->format('l'));

        $business = $this->business();
        $business->update(['hours' => ['schedule' => [$today => ['closed' => true]]]]);

        $this->assertFalse($business->fresh()->isOpenNow());
    }

    public function test_is_open_now_compares_the_current_time_against_todays_hours(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-27 10:00:00'));
        $today = strtolower(now()->format('l'));

        $business = $this->business();
        $business->update(['hours' => ['schedule' => [$today => ['closed' => false, 'open' => '08:00', 'close' => '18:00']]]]);

        $this->assertTrue($business->fresh()->isOpenNow());

        Carbon::setTestNow(Carbon::parse('2026-07-27 20:00:00'));
        $this->assertFalse($business->fresh()->isOpenNow());
    }

    public function test_active_attributes_ignores_deactivated_ones(): void
    {
        $active = BusinessAttribute::create(['name' => 'Producto artesanal', 'slug' => 'producto-artesanal', 'is_active' => true]);
        $inactive = BusinessAttribute::create(['name' => 'Vieja etiqueta', 'slug' => 'vieja-etiqueta', 'is_active' => false]);

        $business = $this->business();
        $business->update(['attributes' => [$active->slug, $inactive->slug]]);

        $names = $business->fresh()->activeAttributes()->pluck('name');

        $this->assertTrue($names->contains('Producto artesanal'));
        $this->assertFalse($names->contains('Vieja etiqueta'));
    }
}
