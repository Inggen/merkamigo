<?php

namespace Tests\Feature\Events\Concerns;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Actions\ManageEventBusinessEquipment;
use App\Domain\Events\Actions\ManageEventDish;
use App\Domain\Events\Actions\ManageEventSpace;
use App\Domain\Events\Actions\UpdateEventSettings;
use App\Domain\Events\Models\EventBusinessEquipment;
use App\Domain\Events\Models\EventDish;
use App\Domain\Events\Models\EventEquipmentType;
use App\Domain\Events\Models\EventSpace;
use App\Domain\Marketplace\Actions\ConnectBusinessWompi;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Setup compartido de negocio listo para Fases 3/4 (reservas + pago) —
 * mismo patrón que `Tests\Feature\Loyalty\Concerns\SetsUpLoyaltyBusiness`.
 * El horario semanal queda ABIERTO todos los días de 00:00 a 23:59 para
 * que las pruebas no tengan que calcular horarios reales y se enfoquen en
 * la regla que están probando.
 */
trait SetsUpEventsBusiness
{
    private static int $eventsBusinessCounter = 0;

    /**
     * @return array{0: Business, 1: User}
     */
    private function eventsReadyBusiness(string $pricingMode = 'platos', ?int $hourlyRateCents = null): array
    {
        self::$eventsBusinessCounter++;
        $n = self::$eventsBusinessCounter;

        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => "Kebero Eventos {$n}",
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        BusinessVerification::create([
            'business_id' => $business->id,
            'status' => BusinessVerification::VERIFICADA,
            'level' => 'basica',
            'expires_at' => now()->addYear(),
        ]);

        Http::fake(['*/merchants/*' => Http::response(['data' => ['id' => 1]], 200)]);

        app(ConnectBusinessWompi::class)->handle($business, [
            'public_key' => "pub_test_{$n}",
            'private_key' => "prv_test_{$n}",
            'integrity_secret' => "integrity-{$n}",
            'events_secret' => "events-secret-{$n}",
            'environment' => 'sandbox',
        ], $owner);

        $openAllWeek = [];
        foreach (Business::DAY_LABELS as $day => $label) {
            $openAllWeek[$day] = ['closed' => false, 'open' => '00:00', 'close' => '23:59'];
        }

        app(UpdateEventSettings::class)->handle($business, [
            'enabled' => true,
            'public_events_enabled' => true,
            'private_reservations_enabled' => true,
            'max_capacity' => 100,
            'max_duration_hours' => 3,
            'min_advance_hours' => 0,
            'hold_minutes' => 30,
            'weekly_schedule' => $openAllWeek,
            'pricing_mode' => $pricingMode,
            'hourly_rate_cents' => $hourlyRateCents,
        ]);

        return [$business->fresh(['eventSetting']), $owner];
    }

    private function eventsSpace(Business $business, int $capacity = 50): EventSpace
    {
        return app(ManageEventSpace::class)->create($business, ['name' => 'Salón principal', 'capacity' => $capacity]);
    }

    private function eventsDish(Business $business, string $name, int $priceCents): EventDish
    {
        return app(ManageEventDish::class)->create($business, ['name' => $name, 'price_cents' => $priceCents]);
    }

    private function eventsGlobalEquipment(Business $business, string $slug, ?int $feeCents = null): EventBusinessEquipment
    {
        $type = EventEquipmentType::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug), 'is_active' => true]);

        return app(ManageEventBusinessEquipment::class)->selectGlobal($business, $type, $feeCents);
    }
}
