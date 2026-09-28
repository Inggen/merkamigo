<?php

namespace Tests\Feature\Moderation;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_user_with_email_and_required_password(): void
    {
        $admin = User::factory()->create();
        $admin->syncPlatformRole('admin');

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction(CreateAction::class, [
                'name' => 'Usuario creado desde admin',
                'email' => 'NUEVO@EXAMPLE.COM',
                'phone' => '',
                'password' => 'password',
                'password_confirmation' => 'password',
                'platform_role' => 'moderator',
            ])
            ->assertHasNoActionErrors();

        $user = User::query()->where('email', 'nuevo@example.com')->firstOrFail();

        $this->assertNull($user->phone);
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertSame('moderator', $user->platformRoleName());
    }

    public function test_admin_can_create_a_user_with_only_a_phone(): void
    {
        $admin = User::factory()->create();
        $admin->syncPlatformRole('admin');

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction(CreateAction::class, [
                'name' => 'Usuario con teléfono',
                'email' => '',
                'phone' => '+573001234567',
                'password' => 'password',
                'password_confirmation' => 'password',
                'platform_role' => '',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'Usuario con teléfono',
            'email' => null,
            'phone' => '+573001234567',
        ]);
    }

    public function test_admin_creation_requires_password_and_one_contact_method(): void
    {
        $admin = User::factory()->create();
        $admin->syncPlatformRole('admin');

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->callAction(CreateAction::class, [
                'name' => 'Usuario incompleto',
                'email' => '',
                'phone' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertHasActionErrors(['email', 'phone', 'password']);

        $this->assertDatabaseMissing('users', ['name' => 'Usuario incompleto']);
    }

    public function test_moderator_cannot_create_users(): void
    {
        $moderator = User::factory()->create();
        $moderator->syncPlatformRole('moderator');

        $this->actingAs($moderator);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(CreateAction::class);
    }
}
