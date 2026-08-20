<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\OpenAiSetting;
use App\Filament\Pages\OpenAiSettings;
use App\Models\User;
use App\Support\Ai\OpenAiTextGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpenAiSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function assignPlatformRole(User $user, string $role): void
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId(User::PLATFORM_TEAM_ID);
        $user->unsetRelation('roles');
        $user->assignRole(Role::findOrCreate($role, 'web'));

        setPermissionsTeamId($previousTeamId);
        $user->unsetRelation('roles');
    }

    public function test_without_saved_settings_it_falls_back_to_service_config(): void
    {
        $setting = OpenAiSetting::current();

        $this->assertSame(config('services.openai.model'), $setting->model());
        $this->assertSame(rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/'), $setting->baseUrl());
    }

    public function test_an_admin_can_save_openai_settings_without_overwriting_the_key_when_left_blank(): void
    {
        OpenAiSetting::create([
            'enabled' => true,
            'api_key' => 'sk-existing',
            'model' => 'gpt-test',
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(OpenAiSettings::class)
            ->fillForm([
                'enabled' => true,
                'entrepreneur_copilot_enabled' => true,
                'model' => 'gpt-new',
                'api_key' => '',
                'base_url' => 'https://api.openai.com/v1',
                'timeout_seconds' => 180,
                'max_output_tokens' => 900,
                'temperature' => 0.4,
                'system_prompt' => 'No inventes datos.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = OpenAiSetting::query()->firstOrFail();

        $this->assertTrue($setting->enabled);
        $this->assertTrue($setting->entrepreneur_copilot_enabled);
        $this->assertSame('gpt-new', $setting->model);
        $this->assertSame('sk-existing', $setting->getRawOriginal('api_key'));
        $this->assertSame(180, $setting->timeout_seconds);
    }

    public function test_the_saved_api_key_is_prefilled_back_into_the_form(): void
    {
        OpenAiSetting::create([
            'enabled' => true,
            'api_key' => 'sk-existing',
            'model' => 'gpt-test',
        ]);

        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(OpenAiSettings::class)
            ->assertSet('data.api_key', 'sk-existing');
    }

    public function test_an_admin_cannot_save_a_non_openai_api_key(): void
    {
        $admin = User::factory()->create();
        $this->assignPlatformRole($admin, 'admin');
        $this->actingAs($admin);

        Livewire::test(OpenAiSettings::class)
            ->fillForm([
                'enabled' => true,
                'entrepreneur_copilot_enabled' => true,
                'model' => 'gpt-new',
                'api_key' => 'AIzaSyAX_jfZfakegooglekey1234567890abc',
                'base_url' => 'https://api.openai.com/v1',
                'timeout_seconds' => 45,
                'max_output_tokens' => 900,
                'temperature' => 0.4,
                'system_prompt' => 'No inventes datos.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $setting = OpenAiSetting::query()->firstOrFail();

        $this->assertNull($setting->getRawOriginal('api_key'));
    }

    public function test_the_api_key_is_trimmed_when_resolved(): void
    {
        $setting = OpenAiSetting::create([
            'enabled' => true,
            'api_key' => '  sk-trimmed-key  ',
            'model' => 'gpt-test',
        ]);

        $this->assertSame('sk-trimmed-key', $setting->apiKey());
    }

    public function test_a_regular_user_cannot_access_the_openai_settings_page(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);

        Livewire::test(OpenAiSettings::class)->assertForbidden();
    }

    public function test_the_openai_text_generator_uses_the_saved_admin_configuration(): void
    {
        OpenAiSetting::create([
            'enabled' => true,
            'api_key' => 'sk-admin-key',
            'model' => 'gpt-admin',
            'base_url' => 'https://api.openai.com/v1',
            'timeout_seconds' => 20,
            'max_output_tokens' => 300,
            'temperature' => 0.2,
            'system_prompt' => 'No inventes datos.',
        ]);

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output' => [
                    [
                        'content' => [
                            ['text' => 'Texto asistido desde OpenAI'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $text = app(OpenAiTextGenerator::class)->generate('Reescribe esto.', ['draft' => 'base']);

        $this->assertSame('Texto asistido desde OpenAI', $text);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer sk-admin-key')
                && $request['model'] === 'gpt-admin';
        });
    }
}
