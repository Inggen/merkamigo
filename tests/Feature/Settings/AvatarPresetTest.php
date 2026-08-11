<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Espeja en la cuenta (`users.avatar_preset`) la elección que
 * `x-immersive.avatar-picker` ya guardó en localStorage, para que la plaza
 * inmersiva pueda mostrar una persona con el mismo preset junto al stand
 * de un dueño de negocio (ver `ImmersivePlazaStandsEndpointTest`).
 */
class AvatarPresetTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_sync_their_chosen_avatar_preset(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::settings.avatar')->call('syncAvatarPreset', 'mujer');

        $this->assertSame('mujer', $user->fresh()->avatar_preset);
    }

    public function test_an_invalid_avatar_preset_is_ignored(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test('pages::settings.avatar')->call('syncAvatarPreset', 'robot');

        $this->assertNull($user->fresh()->avatar_preset);
    }
}
