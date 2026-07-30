<?php

namespace Tests\Feature\Needs;

use App\Domain\Discovery\Models\Municipality;
use App\Domain\Moderation\Actions\RestoreNeed;
use App\Domain\Moderation\Actions\SuspendNeed;
use App\Domain\Needs\Actions\SaveNeedDraft;
use App\Domain\Needs\Models\Need;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 2.1 del TODO: moderación de necesidades.
 */
class NeedModerationTest extends TestCase
{
    use RefreshDatabase;

    private function need(): Need
    {
        $buyer = User::factory()->create();
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);

        $need = app(SaveNeedDraft::class)->handle($buyer, null, [
            'title' => 'Necesidad reportada', 'description' => 'Descripción.', 'municipality_id' => $municipality->id,
        ]);
        $need->update(['status' => Need::PUBLICADA, 'published_at' => now(), 'expires_at' => now()->addDays(14)]);

        return $need->fresh();
    }

    public function test_suspending_a_need_stops_it_from_accepting_offers_and_is_audited(): void
    {
        $need = $this->need();
        $moderator = User::factory()->create();

        app(SuspendNeed::class)->handle($need, $moderator, 'Contenido inapropiado');

        $need->refresh();
        $this->assertTrue($need->isSuspended());
        $this->assertFalse($need->isOpenForOffers());
        $this->assertSame('Contenido inapropiado', $need->suspension_reason);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'need.suspended',
            'subject_id' => $need->id,
        ]);
    }

    public function test_restoring_a_need_lets_it_accept_offers_again(): void
    {
        $need = $this->need();
        $moderator = User::factory()->create();

        app(SuspendNeed::class)->handle($need, $moderator, 'Motivo');
        app(RestoreNeed::class)->handle($need->fresh(), $moderator);

        $need->refresh();
        $this->assertFalse($need->isSuspended());
        $this->assertTrue($need->isOpenForOffers());
        $this->assertNull($need->suspension_reason);
    }

    public function test_a_suspended_need_does_not_appear_in_pidelo(): void
    {
        $need = $this->need();
        app(SuspendNeed::class)->handle($need, User::factory()->create(), 'Motivo');

        $this->withUnencryptedCookie('municipio', 'cajica')
            ->get(route('pidelo'))
            ->assertOk()
            ->assertDontSee($need->title);
    }
}
