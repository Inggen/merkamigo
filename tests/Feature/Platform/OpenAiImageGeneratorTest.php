<?php

namespace Tests\Feature\Platform;

use App\Domain\Platform\Models\OpenAiSetting;
use App\Support\Ai\OpenAiImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAiImageGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function enabledSettings(): void
    {
        OpenAiSetting::create([
            'enabled' => true,
            'api_key' => 'sk-admin-key',
            'model' => 'gpt-admin',
            'image_model' => 'gpt-image-2',
            'base_url' => 'https://api.openai.com/v1',
            'timeout_seconds' => 20,
        ]);
    }

    public function test_it_sends_the_configured_image_model_and_decodes_a_base64_image(): void
    {
        $this->enabledSettings();

        Http::fake([
            'https://api.openai.com/v1/images/generations' => Http::response([
                'data' => [['b64_json' => base64_encode('contenido-binario-de-prueba')]],
            ], 200),
        ]);

        $result = app(OpenAiImageGenerator::class)->generate('Fotografía de prueba.');

        $this->assertSame('contenido-binario-de-prueba', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/images/generations'
                && $request->hasHeader('Authorization', 'Bearer sk-admin-key')
                && $request['model'] === 'gpt-image-2'
                && $request['prompt'] === 'Fotografía de prueba.';
        });
    }

    public function test_it_downloads_the_image_when_the_response_only_has_a_url(): void
    {
        $this->enabledSettings();

        Http::fake([
            'https://api.openai.com/v1/images/generations' => Http::response([
                'data' => [['url' => 'https://cdn.example.com/generated.png']],
            ], 200),
            'https://cdn.example.com/generated.png' => Http::response('contenido-de-la-url', 200),
        ]);

        $result = app(OpenAiImageGenerator::class)->generate('Fotografía de prueba.');

        $this->assertSame('contenido-de-la-url', $result);
    }

    public function test_it_returns_null_when_disabled(): void
    {
        OpenAiSetting::create(['enabled' => false, 'api_key' => 'sk-admin-key', 'model' => 'gpt-admin']);

        $this->assertNull(app(OpenAiImageGenerator::class)->generate('Fotografía de prueba.'));
    }

    public function test_it_returns_null_when_the_request_fails(): void
    {
        $this->enabledSettings();

        Http::fake([
            'https://api.openai.com/v1/images/generations' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->assertNull(app(OpenAiImageGenerator::class)->generate('Fotografía de prueba.'));
    }
}
