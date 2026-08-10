<?php

namespace Tests\Feature\Immersive;

use App\Domain\Immersive\Support\Exceptions\VoxelGenerationException;
use App\Domain\Immersive\Support\Exceptions\VoxelGenerationTimeoutException;
use App\Domain\Immersive\Support\OpenAiVoxelObjectGenerator;
use App\Domain\Immersive\Support\VoxelDefinitionValidator;
use App\Domain\Platform\Models\OpenAiSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * IMM-020b del TODO inmersivo: generador OpenAI con visión + JSON
 * estructurado. A diferencia de `OpenAiTextGenerator`, nunca falla en
 * silencio — siempre lanza `VoxelGenerationException`.
 */
class OpenAiVoxelObjectGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::disk('public')->put('immersive-object-templates/ai-source/front.jpg', 'fake-bytes');
    }

    private function enableOpenAi(): void
    {
        OpenAiSetting::create([
            'enabled' => true,
            'api_key' => 'sk-test',
            'model' => 'gpt-test',
            'base_url' => 'https://api.openai.com/v1',
            'timeout_seconds' => 30,
        ]);
    }

    public function test_it_decodes_a_valid_response_into_a_definition_array(): void
    {
        $this->enableOpenAi();

        $definition = ['version' => 1, 'boxes' => [['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false]]];

        Http::fake([
            '*/responses' => Http::response(['output_text' => json_encode($definition)], 200),
        ]);

        $result = (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'una mesa pequeña',
        );

        $this->assertSame($definition, $result);
    }

    public function test_it_throws_when_openai_is_disabled(): void
    {
        OpenAiSetting::create(['enabled' => false]);

        Http::fake();

        try {
            (new OpenAiVoxelObjectGenerator)->generate(['front' => 'immersive-object-templates/ai-source/front.jpg'], 'instrucciones');
            $this->fail('Se esperaba VoxelGenerationException.');
        } catch (VoxelGenerationException) {
            Http::assertNothingSent();
        }
    }

    public function test_it_throws_when_the_http_call_is_not_successful(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['error' => 'boom'], 500),
        ]);

        $this->expectException(VoxelGenerationException::class);

        (new OpenAiVoxelObjectGenerator)->generate(['front' => 'immersive-object-templates/ai-source/front.jpg'], 'instrucciones');
    }

    public function test_it_throws_when_the_response_is_not_valid_json(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['output_text' => 'esto no es json'], 200),
        ]);

        $this->expectException(VoxelGenerationException::class);

        (new OpenAiVoxelObjectGenerator)->generate(['front' => 'immersive-object-templates/ai-source/front.jpg'], 'instrucciones');
    }

    public function test_the_payload_sends_images_as_base64_and_requests_a_json_schema(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'instrucciones',
        );

        Http::assertSent(function ($request) {
            $content = $request->data()['input'][0]['content'];
            // 'front' es una de las 3 vistas ortográficas: llevan detail
            // 'high' porque de ellas depende medir proporciones reales;
            // solo la miniatura/preview usa 'low' (ver test de abajo).
            $hasImage = collect($content)->contains(fn ($part) => ($part['type'] ?? null) === 'input_image'
                && str_starts_with($part['image_url'], 'data:image/')
                && ($part['detail'] ?? null) === 'high');

            return $hasImage
                && $request->data()['text']['format']['type'] === 'json_schema'
                && $request->data()['text']['format']['strict'] === true;
        });
    }

    /**
     * Pedido del usuario: cada imagen debe ir etiquetada (para que el
     * modelo sepa qué vista es cada una) y la miniatura del catálogo debe
     * llevar menos detail que las vistas ortográficas reales, porque no
     * tiene por qué respetar proporciones exactas.
     */
    public function test_each_image_is_labeled_by_view_with_the_right_detail_level(): void
    {
        $this->enableOpenAi();
        Storage::disk('public')->put('immersive-object-templates/ai-source/side.jpg', 'fake-bytes');
        Storage::disk('public')->put('immersive-object-templates/ai-source/top.jpg', 'fake-bytes');
        Storage::disk('public')->put('immersive-object-templates/thumb.png', 'fake-bytes');

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(
            [
                'front' => 'immersive-object-templates/ai-source/front.jpg',
                'side' => 'immersive-object-templates/ai-source/side.jpg',
                'top' => 'immersive-object-templates/ai-source/top.jpg',
                'preview' => 'immersive-object-templates/thumb.png',
            ],
            'instrucciones',
        );

        Http::assertSent(function ($request) {
            $content = $request->data()['input'][0]['content'];
            $texts = collect($content)->where('type', 'input_text')->pluck('text')->implode("\n---\n");
            $images = collect($content)->where('type', 'input_image')->values();

            return str_contains($texts, 'REFERENCIA FRONTAL')
                && str_contains($texts, 'REFERENCIA LATERAL')
                && str_contains($texts, 'REFERENCIA SUPERIOR')
                && str_contains($texts, 'REFERENCIA ADICIONAL (miniatura del catálogo)')
                && $images->count() === 4
                && $images[0]['detail'] === 'high'
                && $images[1]['detail'] === 'high'
                && $images[2]['detail'] === 'high'
                && $images[3]['detail'] === 'low';
        });
    }

    /**
     * "NO asumir que siempre estarán las cuatro" — el generador debe
     * funcionar con cualquier subconjunto de referencias, no solo con el
     * set completo.
     */
    public function test_it_works_with_only_a_single_reference_image(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        $result = (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'instrucciones',
        );

        $this->assertSame(['version' => 1, 'boxes' => []], $result);

        Http::assertSent(function ($request) {
            $images = collect($request->data()['input'][0]['content'])->where('type', 'input_image');

            return $images->count() === 1;
        });
    }

    public function test_a_refinement_includes_the_previous_definition_and_refinement_instructions(): void
    {
        $this->enableOpenAi();

        $previousDefinition = ['version' => 1, 'boxes' => [
            ['x' => 0, 'y' => 1, 'z' => 0, 'w' => 1, 'h' => 1, 'd' => 1, 'texture' => 'wood', 'rotationY' => 0, 'collidable' => false],
        ]];

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'hazlo más alto',
            [],
            $previousDefinition,
        );

        Http::assertSent(function ($request) use ($previousDefinition) {
            $text = $request->data()['input'][0]['content'][0]['text'];

            return str_contains($text, 'REFINAMIENTO')
                && str_contains($text, 'nunca un diff')
                && str_contains($text, json_encode($previousDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        });
    }

    /**
     * Bug real (ver Statua condor en producción local): la respuesta de un
     * refinamiento debe seguir devolviendo la definición COMPLETA, nunca un
     * diff — este test protege que el schema y el flujo de decodificación
     * sigan aceptando una respuesta completa igual que en una generación
     * nueva.
     */
    public function test_the_box_schema_fields_remain_compatible_with_existing_definitions(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'instrucciones',
        );

        Http::assertSent(function ($request) {
            $boxSchema = $request->data()['text']['format']['schema']['properties']['boxes']['items'];

            return $boxSchema['required'] === ['x', 'y', 'z', 'w', 'h', 'd', 'texture', 'rotationY', 'collidable']
                && $boxSchema['additionalProperties'] === false
                && array_keys($boxSchema['properties']) === ['x', 'y', 'z', 'w', 'h', 'd', 'texture', 'rotationY', 'collidable'];
        });
    }

    /**
     * Estrategia de recuperación pedida por el usuario: un timeout del
     * cliente HTTP (`ConnectionException`) es la única causa de fallo que el
     * código puede atribuir con confianza a "demasiada complejidad para el
     * tiempo disponible" — dispara exactamente UN reintento automático con
     * un `maxBoxes` más conservador (60%, mínimo 8) y una instrucción extra
     * pidiendo simplificar.
     */
    public function test_a_timeout_triggers_exactly_one_retry_with_a_reduced_max_boxes(): void
    {
        $this->enableOpenAi();

        $calls = [];
        Http::fake(function ($request) use (&$calls) {
            $calls[] = $request->data();

            if (count($calls) === 1) {
                throw new ConnectionException('cURL error 28: Operation timed out');
            }

            return Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200);
        });

        $result = (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'instrucciones',
            ['max_boxes' => 20],
        );

        $this->assertSame(['version' => 1, 'boxes' => []], $result);
        $this->assertCount(2, $calls);
        $this->assertSame(20, $calls[0]['text']['format']['schema']['properties']['boxes']['maxItems']);
        $this->assertSame(12, $calls[1]['text']['format']['schema']['properties']['boxes']['maxItems']);

        $retryText = $calls[1]['input'][0]['content'][0]['text'];
        $this->assertStringContainsString('Reintento automático', $retryText);
    }

    /**
     * El reintento nunca debe convertirse en un bucle: si también falla por
     * timeout, la excepción se propaga tal cual y no se reintenta una
     * tercera vez.
     */
    public function test_if_the_retry_also_times_out_it_does_not_retry_again(): void
    {
        $this->enableOpenAi();

        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            (new OpenAiVoxelObjectGenerator)->generate(
                ['front' => 'immersive-object-templates/ai-source/front.jpg'],
                'instrucciones',
            );

            $this->fail('Se esperaba VoxelGenerationTimeoutException.');
        } catch (VoxelGenerationTimeoutException) {
            $this->assertSame(2, $calls);
        }
    }

    /**
     * Bug real encontrado en producción local: la API de OpenAI responde
     * 400 "Unsupported parameter" si se envía `reasoning`/`temperature` a un
     * modelo que no los soporta en cada dirección (los de razonamiento
     * rechazan `temperature`; los que no son de razonamiento rechazan
     * `reasoning`). Antes de este fix, `reasoning.effort` no se enviaba
     * nunca, así que gpt-5-mini tardaba 30-90s+ razonando sin control y
     * agotaba cualquier timeout sincrónico razonable.
     */
    public function test_reasoning_models_receive_a_low_reasoning_effort_and_no_temperature(): void
    {
        OpenAiSetting::create([
            'enabled' => true,
            'api_key' => 'sk-test',
            'model' => 'gpt-5-mini',
            'temperature' => 0.7,
            'base_url' => 'https://api.openai.com/v1',
            'timeout_seconds' => 30,
        ]);

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(['front' => 'immersive-object-templates/ai-source/front.jpg'], 'instrucciones');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return ($data['reasoning']['effort'] ?? null) === 'low'
                && ! array_key_exists('temperature', $data);
        });
    }

    public function test_non_reasoning_models_receive_temperature_and_no_reasoning_parameter(): void
    {
        OpenAiSetting::create([
            'enabled' => true,
            'api_key' => 'sk-test',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.7,
            'base_url' => 'https://api.openai.com/v1',
            'timeout_seconds' => 30,
        ]);

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(['front' => 'immersive-object-templates/ai-source/front.jpg'], 'instrucciones');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return ($data['temperature'] ?? null) === 0.7
                && ! array_key_exists('reasoning', $data);
        });
    }

    /**
     * IMM-020b: `max_boxes` es configurable por plantilla (una catedral
     * necesita muchas más cajas que un stand) — debe reflejarse tanto en el
     * `maxItems` del schema como en el texto del prompt, no en la constante
     * fija que existía antes de este cambio.
     */
    public function test_the_context_max_boxes_overrides_the_schema_and_prompt_limit(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'instrucciones',
            ['max_boxes' => 150],
        );

        Http::assertSent(function ($request) {
            $data = $request->data();
            $text = $data['input'][0]['content'][0]['text'] ?? '';

            return $data['text']['format']['schema']['properties']['boxes']['maxItems'] === 150
                && str_contains($text, '150 cajas');
        });
    }

    /**
     * Bug real reportado: "Colores permitidos" se guardaba en la plantilla
     * pero nunca llegaba a la IA ni al schema, así que no tenía ningún
     * efecto. `context['allowed_textures']` (ya resuelto por
     * `VoxelPaletteMatcher` en el llamador) debe restringir de verdad el
     * enum del schema — la aplicación dura, no solo un pedido de palabra.
     */
    public function test_allowed_textures_in_context_restrict_the_schema_enum(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'instrucciones',
            ['allowed_textures' => ['wood', 'roofClay']],
        );

        Http::assertSent(function ($request) {
            $data = $request->data();
            $enum = $data['text']['format']['schema']['properties']['boxes']['items']['properties']['texture']['enum'];

            return $enum === ['wood', 'roofClay']
                && str_contains($data['instructions'], 'PALETA RESTRINGIDA')
                && str_contains($data['instructions'], 'wood, roofClay');
        });
    }

    /**
     * Sin "Colores permitidos" (caso normal, sin restricción) el vocabulario
     * completo sigue disponible — no hay regresión para plantillas que
     * nunca usaron ese campo.
     */
    public function test_without_allowed_textures_the_full_vocabulary_is_used(): void
    {
        $this->enableOpenAi();

        Http::fake([
            '*/responses' => Http::response(['output_text' => '{"version":1,"boxes":[]}'], 200),
        ]);

        (new OpenAiVoxelObjectGenerator)->generate(
            ['front' => 'immersive-object-templates/ai-source/front.jpg'],
            'instrucciones',
        );

        Http::assertSent(function ($request) {
            $enum = $request->data()['text']['format']['schema']['properties']['boxes']['items']['properties']['texture']['enum'];

            return $enum === VoxelDefinitionValidator::ALLOWED_TEXTURES;
        });
    }
}
