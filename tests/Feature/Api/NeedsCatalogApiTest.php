<?php

namespace Tests\Feature\Api;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Models\Need;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `GET /api/v1/needs` (5.1/2.1 del TODO): catálogo público de necesidades
 * abiertas, distinto del `show`/`update` privado ya existente.
 */
class NeedsCatalogApiTest extends TestCase
{
    use AssertsApiEnvelope;
    use RefreshDatabase;

    public function test_the_public_catalog_lists_only_open_needs_in_the_given_municipality(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $otherMunicipality = Municipality::create(['name' => 'Zipaquirá', 'slug' => 'zipaquira', 'department' => 'Cundinamarca', 'is_active' => true]);

        $open = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Necesito un electricista', 'description' => 'Urgente.', 'municipality_id' => $municipality->id,
        ]);
        $open->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $closed = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Ya cerrada', 'description' => 'x', 'municipality_id' => $municipality->id,
        ]);
        $closed->update(['status' => Need::CERRADA]);

        $elsewhere = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'En otro municipio', 'description' => 'x', 'municipality_id' => $otherMunicipality->id,
        ]);
        $elsewhere->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        $response = $this->getJson(route('api.v1.needs.index', ['municipio_id' => $municipality->id]));

        $this->assertPaginatedEnvelope($response)->assertOk();
        $response->assertJsonPath('data.0.title', 'Necesito un electricista');
        $this->assertCount(1, $response->json('data'));
    }

    public function test_the_catalog_requires_a_municipality(): void
    {
        $this->getJson(route('api.v1.needs.index'))->assertUnprocessable();
    }
}
