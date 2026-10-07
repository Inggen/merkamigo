<?php

namespace Tests\Feature\Events;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Events\Models\EventEquipmentType;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Database\Seeders\EventEquipmentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Panel de Eventos del negocio (TODO_desarrollo_sistema_eventos_Merkamigo.md,
 * Fase 2). Criterio de aceptación: "un dueño configura Kebero con máximo
 * tres horas, platos de prueba, sonido/micrófono/televisor y cualquiera
 * de las tres tarifas sin tocar código."
 */
class EventsBusinessPanelTest extends TestCase
{
    use RefreshDatabase;

    private function freshBusiness(): array
    {
        $municipality = Municipality::firstOrCreate(['slug' => 'cajica'], ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = Category::firstOrCreate(['slug' => 'alimentos'], ['name' => 'Alimentos', 'is_active' => true]);
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => 'Kebero Música y Café '.uniqid(),
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        return [$business->fresh(), $owner];
    }

    private function addCollaborator(Business $business): User
    {
        $collaborator = User::factory()->create();

        $previousTeamId = getPermissionsTeamId();
        setPermissionsTeamId($business->id);
        $collaborator->assignRole(Role::findOrCreate('collaborator', 'web'));
        setPermissionsTeamId($previousTeamId);

        return $collaborator;
    }

    public function test_the_owner_can_configure_general_settings_and_the_weekly_schedule(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('enabled', true)
            ->set('publicEventsEnabled', true)
            ->set('maxCapacity', 30)
            ->set('maxDurationHours', 3)
            ->set('minAdvanceHours', 24)
            ->set('weeklySchedule.tuesday.closed', false)
            ->set('weeklySchedule.tuesday.open', '10:00')
            ->set('weeklySchedule.tuesday.close', '22:00')
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $settings = $business->eventSetting()->first();

        $this->assertNotNull($settings);
        $this->assertTrue($settings->enabled);
        $this->assertTrue($settings->public_events_enabled);
        $this->assertSame(30, $settings->max_capacity);
        $this->assertSame(3, $settings->max_duration_hours);
        $this->assertFalse($settings->weekly_schedule['tuesday']['closed']);
        $this->assertSame('10:00', $settings->weekly_schedule['tuesday']['open']);
    }

    public function test_private_reservations_cannot_be_enabled_without_a_connected_wompi_account(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('privateReservationsEnabled', true)
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $this->assertFalse($business->eventSetting?->private_reservations_enabled ?? false);
    }

    public function test_private_reservations_can_be_enabled_once_wompi_is_connected(): void
    {
        [$business, $owner] = $this->freshBusiness();

        $business->wompiCredential()->create([
            'public_key' => 'pub_test_events',
            'private_key' => 'prv_test_events',
            'integrity_secret' => 'integrity-events',
            'events_secret' => 'events-secret',
            'environment' => 'sandbox',
            'is_active' => true,
            'connected_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('privateReservationsEnabled', true)
            ->call('saveGeneral')
            ->assertHasNoErrors();

        $this->assertTrue($business->eventSetting()->first()->private_reservations_enabled);
    }

    public function test_the_owner_can_set_any_of_the_three_pricing_modes(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('pricingMode', 'hibrido')
            ->set('hourlyRateCop', 80000)
            ->call('saveRates')
            ->assertHasNoErrors();

        $settings = $business->eventSetting()->first();

        $this->assertSame('hibrido', $settings->pricing_mode);
        $this->assertSame(8000000, $settings->hourly_rate_cents);
    }

    public function test_an_hourly_rate_is_required_for_non_dish_pricing_modes(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('pricingMode', 'horas')
            ->set('hourlyRateCop', null)
            ->call('saveRates')
            ->assertHasNoErrors();

        $this->assertNull($business->eventSetting);
    }

    public function test_the_owner_can_add_test_dishes(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('dishName', 'Crepes')
            ->set('dishPriceCop', 25000)
            ->call('addDish')
            ->assertHasNoErrors()
            ->set('dishName', 'Sándwiches')
            ->set('dishPriceCop', 30000)
            ->call('addDish')
            ->assertHasNoErrors();

        $this->assertSame(2, $business->eventDishes()->count());
        $this->assertSame(2500000, $business->eventDishes()->where('name', 'Crepes')->first()->price_cents);
    }

    public function test_the_owner_can_select_sonido_microfono_and_televisor_from_the_global_catalog(): void
    {
        [$business, $owner] = $this->freshBusiness();
        (new EventEquipmentTypeSeeder)->run();

        $sonido = EventEquipmentType::where('slug', 'sonido')->firstOrFail();
        $microfono = EventEquipmentType::where('slug', 'microfono')->firstOrFail();
        $televisor = EventEquipmentType::where('slug', 'televisor')->firstOrFail();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('toggleGlobalEquipment', $sonido->id)
            ->call('toggleGlobalEquipment', $microfono->id)
            ->call('toggleGlobalEquipment', $televisor->id);

        $component->assertHasNoErrors();

        $selectedTypeIds = $business->eventEquipment()->pluck('event_equipment_type_id')->sort()->values()->all();
        $this->assertSame([$sonido->id, $microfono->id, $televisor->id], collect($selectedTypeIds)->sort()->values()->all());
    }

    public function test_toggling_the_same_global_equipment_twice_removes_it(): void
    {
        [$business, $owner] = $this->freshBusiness();
        (new EventEquipmentTypeSeeder)->run();

        $sonido = EventEquipmentType::where('slug', 'sonido')->firstOrFail();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->call('toggleGlobalEquipment', $sonido->id)
            ->call('toggleGlobalEquipment', $sonido->id);

        $this->assertSame(0, $business->eventEquipment()->count());
    }

    public function test_the_owner_can_add_a_custom_equipment(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('customEquipmentName', 'Máquina de humo')
            ->set('customEquipmentFeeCop', 50000)
            ->call('addCustomEquipment')
            ->assertHasNoErrors();

        $equipment = $business->eventEquipment()->first();
        $this->assertSame('Máquina de humo', $equipment->custom_name);
        $this->assertSame(5000000, $equipment->fee_cents);
    }

    public function test_the_owner_can_add_a_space_and_block_a_date(): void
    {
        [$business, $owner] = $this->freshBusiness();

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('spaceName', 'Salón principal')
            ->set('spaceCapacity', 40)
            ->call('addSpace')
            ->assertHasNoErrors()
            ->set('blockedDate', now()->addWeek()->toDateString())
            ->set('blockedReason', 'Mantenimiento')
            ->call('addBlockedDate')
            ->assertHasNoErrors();

        $this->assertSame(1, $business->eventSpaces()->count());
        $this->assertSame('Salón principal', $business->eventSpaces()->first()->name);
        $this->assertSame(1, $business->eventBlockedDates()->count());
    }

    public function test_the_owner_can_add_a_space_with_an_image(): void
    {
        Storage::fake('public');
        [$business, $owner] = $this->freshBusiness();

        $component = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('spaceName', 'Terraza panorámica')
            ->set('spaceCapacity', 25)
            ->set('spaceImage', UploadedFile::fake()->image('terraza.jpg', 1200, 800))
            ->call('addSpace')
            ->assertHasNoErrors();

        $space = $business->eventSpaces()->firstOrFail();
        $originalImagePath = $space->image_path;

        $this->assertNotNull($originalImagePath);
        Storage::disk('public')->assertExists($originalImagePath);

        $component
            ->call('editSpace', $space->id)
            ->assertSet('spaceName', 'Terraza panorámica')
            ->set('spaceName', 'Terraza renovada')
            ->set('spaceImage', UploadedFile::fake()->image('terraza-nueva.jpg', 1200, 800))
            ->call('addSpace')
            ->assertHasNoErrors();

        $space->refresh();

        $this->assertSame('Terraza renovada', $space->name);
        $this->assertNotSame($originalImagePath, $space->image_path);
        Storage::disk('public')->assertMissing($originalImagePath);
        Storage::disk('public')->assertExists($space->image_path);

        $component->call('deleteSpace', $space->id)->assertHasNoErrors();

        Storage::disk('public')->assertMissing($space->image_path);
    }

    public function test_a_collaborator_cannot_change_event_settings(): void
    {
        [$business] = $this->freshBusiness();
        $collaborator = $this->addCollaborator($business);

        Livewire::actingAs($collaborator)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $business])
            ->set('enabled', true)
            ->call('saveGeneral');

        $this->assertNull($business->eventSetting);
    }

    public function test_a_business_cannot_see_another_businesss_event_settings_panel(): void
    {
        [$businessA] = $this->freshBusiness();
        [, $ownerB] = $this->freshBusiness();

        Livewire::actingAs($ownerB)
            ->test('pages::emprendedores.negocios.eventos', ['business' => $businessA])
            ->assertForbidden();
    }
}
