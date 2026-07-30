<?php

namespace Tests\Feature\Needs;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Models\Need;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2.1/2.3 del TODO: "cerrar por vencimiento".
 */
class ExpireOverdueNeedsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_command_closes_only_overdue_published_needs(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $overdue = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Vencida', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);
        $overdue->update(['status' => Need::PUBLICADA, 'published_at' => now()->subDays(20), 'expires_at' => now()->subDay()]);

        $active = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Activa', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);
        $active->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(10)]);

        $draft = app(SaveNeedDraft::class)->handle(User::factory()->create(), null, [
            'title' => 'Borrador', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);

        $this->artisan('needs:expire-overdue')->assertSuccessful();

        $this->assertSame(Need::VENCIDA, $overdue->fresh()->status);
        $this->assertNotNull($overdue->fresh()->closed_at);
        $this->assertSame(Need::PUBLICADA, $active->fresh()->status);
        $this->assertSame(Need::BORRADOR, $draft->fresh()->status);
    }
}
