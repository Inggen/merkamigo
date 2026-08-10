<?php

namespace App\Domain\Immersive\Support;

use App\Domain\Immersive\Contracts\GeneratesVoxelObjectDefinition;
use App\Domain\Immersive\Support\Exceptions\VoxelGenerationException;
use App\Domain\Immersive\Support\Exceptions\VoxelGenerationTimeoutException;
use App\Domain\Platform\Models\OpenAiSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * IMM-020b: implementación OpenAI de `GeneratesVoxelObjectDefinition`.
 * Reutiliza la misma configuración singleton que `OpenAiTextGenerator`
 * (`OpenAiSetting`), pero llama a la Responses API con visión
 * (`input_image`) y salida JSON estructurada (`text.format` tipo
 * `json_schema`) — capacidades que el generador de texto asistido no usa.
 *
 * A diferencia de `OpenAiTextGenerator::generate()`, que devuelve `null` en
 * silencio si algo falla, aquí siempre se lanza `VoxelGenerationException`:
 * no existe un "objeto sin IA" razonable al que volver.
 */
class OpenAiVoxelObjectGenerator implements GeneratesVoxelObjectDefinition
{
    private const MAX_SYNC_TIMEOUT_SECONDS = 90;

    private const MAX_IMAGE_WIDTH = 1024;

    private const MAX_BOXES = 80;

    /**
     * Etiqueta y nivel de detalle por vista. `detail: 'high'` en las tres
     * vistas ortográficas (miden proporciones reales) y `'low'` en la
     * miniatura/preview (solo aporta estilo y lectura general, nunca
     * dimensiones exactas) — mantiene el costo/latencia acotados sin bajarle
     * detalle a las imágenes que sí importan para la geometría.
     *
     * @var array<string, array{title: string, hint: string, detail: string}>
     */
    private const VIEW_LABELS = [
        'front' => [
            'title' => 'REFERENCIA FRONTAL',
            'hint' => 'Vista frontal del mismo objeto. Úsala principalmente para ancho, altura y silueta frontal.',
            'detail' => 'high',
        ],
        'side' => [
            'title' => 'REFERENCIA LATERAL',
            'hint' => 'Vista lateral del mismo objeto. Úsala para profundidad y perfil.',
            'detail' => 'high',
        ],
        'top' => [
            'title' => 'REFERENCIA SUPERIOR',
            'hint' => 'Vista cenital del mismo objeto. Úsala para huella y distribución.',
            'detail' => 'high',
        ],
        'preview' => [
            'title' => 'REFERENCIA ADICIONAL (miniatura del catálogo)',
            'hint' => 'Referencia visual del mismo objeto. Úsala para comprender apariencia, estilo voxel y nivel de '
                .'detalle esperado. No tiene por qué tener proporciones geométricas exactas: las vistas frontal/'
                .'lateral/superior pesan más para dimensiones.',
            'detail' => 'low',
        ],
    ];

    private const FALLBACK_VIEW_LABEL = [
        'title' => 'REFERENCIA ADICIONAL',
        'hint' => 'Otra vista de referencia del mismo objeto.',
        'detail' => 'low',
    ];

    /**
     * @param  array<string, string>  $imagePaths  Rutas en el disco 'public', indexadas por vista
     *                                             ('front'/'side'/'top'/'preview'; cualquier otra clave se trata
     *                                             como referencia genérica adicional). No es obligatorio traer
     *                                             las cuatro — el llamador decide cuáles existen.
     */
    public function generate(
        array $imagePaths,
        string $instructions,
        array $context = [],
        ?array $previousDefinition = null,
    ): array {
        $settings = OpenAiSetting::current();

        if (! $settings->isEnabled()) {
            throw new VoxelGenerationException('La integración de OpenAI no está configurada o está deshabilitada.');
        }

        // IMM-020b: límite por plantilla (`max_boxes`, configurable en el
        // formulario) — una catedral necesita muchas más cajas que un
        // stand. `context['max_boxes']` llega desde el componente Livewire
        // con `$template->max_boxes`; el fallback solo cubre llamadas
        // directas al contrato sin ese contexto.
        $maxBoxes = (int) ($context['max_boxes'] ?? self::MAX_BOXES);

        try {
            return $this->attempt($imagePaths, $instructions, $context, $previousDefinition, $maxBoxes, $settings);
        } catch (VoxelGenerationTimeoutException) {
            // Único reintento automático, y solo para esta causa: un timeout
            // del cliente HTTP es la única señal que el código puede
            // atribuir con confianza a "la complejidad pedida no cupo en el
            // tiempo disponible" (ver `attempt()`). Cualquier otro fallo
            // (auth, HTTP 4xx/5xx, JSON inválido, red caída) no reintenta —
            // sale de este catch sin capturarlo y se propaga tal cual.
            $retryMaxBoxes = max(8, (int) floor($maxBoxes * 0.6));

            Log::warning('inmersivo.voxel_generation.retry_after_timeout', [
                'model' => $settings->model(),
                'original_max_boxes' => $maxBoxes,
                'retry_max_boxes' => $retryMaxBoxes,
                'image_count' => count($imagePaths),
            ]);

            $retryInstructions = trim($instructions) !== ''
                ? trim($instructions)."\n\n(Reintento automático: el primer intento no completó a tiempo por exceso "
                    .'de complejidad. Reduce detalles secundarios manteniendo silueta y partes distintivas.)'
                : 'Reintento automático: el primer intento no completó a tiempo por exceso de complejidad. Reduce '
                    .'detalles secundarios manteniendo silueta y partes distintivas.';

            // Se reintenta UNA sola vez: este segundo `attempt()` ya no está
            // envuelto en ningún catch de `VoxelGenerationTimeoutException`,
            // así que si también falla (por timeout o cualquier otra causa)
            // la excepción sale de `generate()` sin más reintentos.
            return $this->attempt(
                $imagePaths,
                $retryInstructions,
                [...$context, 'max_boxes' => $retryMaxBoxes],
                $previousDefinition,
                $retryMaxBoxes,
                $settings,
            );
        }
    }

    /**
     * Un intento completo: arma el payload, llama a la API y decodifica la
     * respuesta. Separado de `generate()` para que el reintento (única
     * causa: timeout) pueda repetir exactamente esta lógica con un
     * `maxBoxes` más conservador sin duplicar código.
     *
     * @param  array<string, string>  $imagePaths
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $previousDefinition
     * @return array<string, mixed>
     */
    private function attempt(
        array $imagePaths,
        string $instructions,
        array $context,
        ?array $previousDefinition,
        int $maxBoxes,
        OpenAiSetting $settings,
    ): array {
        $requestTimeout = min($settings->timeoutSeconds(), self::MAX_SYNC_TIMEOUT_SECONDS);

        $this->extendExecutionWindow($requestTimeout);

        // "Colores permitidos" (admin) llega ya traducido a nombres de
        // textura en $context['allowed_textures'] (VoxelPaletteMatcher) —
        // el motor no admite colores hex libres, solo texturas con nombre y
        // color fijo. Sin restricción explícita (campo vacío), se usa el
        // vocabulario completo, igual que siempre.
        $allowedTextures = $context['allowed_textures'] ?? [];
        $allowedTextures = is_array($allowedTextures) && $allowedTextures !== []
            // array_filter (no array_intersect) a propósito: conserva el
            // orden en que el admin pidió los colores en vez de reordenar
            // según la posición interna en ALLOWED_TEXTURES.
            ? array_values(array_filter(
                $allowedTextures,
                static fn ($texture) => in_array($texture, VoxelDefinitionValidator::ALLOWED_TEXTURES, true)
            ))
            : VoxelDefinitionValidator::ALLOWED_TEXTURES;

        if ($allowedTextures === []) {
            $allowedTextures = VoxelDefinitionValidator::ALLOWED_TEXTURES;
        }

        $payload = array_filter([
            'model' => $settings->model(),
            'input' => [[
                'role' => 'user',
                'content' => $this->buildContent($imagePaths, $instructions, $context, $previousDefinition, $maxBoxes),
            ]],
            'instructions' => $this->systemPrompt($maxBoxes, $allowedTextures),
            'temperature' => $this->isReasoningModel($settings->model()) ? null : $settings->temperature(),
            'max_output_tokens' => $settings->maxOutputTokens(),
            // Los modelos de razonamiento (o1/o3/o4-mini/gpt-5*) razonan
            // antes de responder; sin bajar el esfuerzo, una llamada con
            // varias imágenes + schema estricto tarda 30-90s+. 'low' la deja
            // en ~10-20s sin perder la validación estructurada. OJO: a
            // diferencia de otros parámetros, la API SÍ rechaza
            // `reasoning` con 400 en modelos que no lo soportan (probado
            // manualmente) — nunca enviarlo salvo que el modelo sea de
            // razonamiento. Mismo motivo para omitir `temperature`: los
            // modelos de razonamiento tampoco la aceptan.
            'reasoning' => $this->isReasoningModel($settings->model()) ? ['effort' => 'low'] : null,
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'voxel_object_definition',
                    // El enum del schema es la aplicación DURA de "colores
                    // permitidos": la API garantiza (structured outputs,
                    // strict:true) que el modelo no puede emitir una
                    // textura fuera de esta lista, así que no depende de que
                    // la IA "obedezca" el prompt.
                    'schema' => $this->jsonSchema($maxBoxes, $allowedTextures),
                    'strict' => true,
                ],
            ],
        ], fn ($value) => $value !== null && $value !== '');

        try {
            $response = Http::withToken($settings->apiKey())
                ->acceptJson()
                ->asJson()
                ->timeout($requestTimeout)
                ->post($settings->baseUrl().'/responses', $payload);

            if (! $response->successful()) {
                Log::warning('inmersivo.voxel_generation.http_error', [
                    'model' => $settings->model(),
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);

                throw new VoxelGenerationException(
                    'OpenAI respondió con un error: '.$response->status().' '.$response->body()
                );
            }

            return $this->extractDefinition($response->json());
        } catch (ConnectionException $exception) {
            // Única causa de fallo que se puede atribuir con confianza a
            // "demasiada complejidad para el tiempo disponible" (el cliente
            // HTTP agotó `$requestTimeout` esperando respuesta) — por eso es
            // la única que dispara el reintento automático en `generate()`.
            Log::warning('inmersivo.voxel_generation.timeout', [
                'model' => $settings->model(),
                'max_boxes' => $maxBoxes,
                'image_count' => count($imagePaths),
                'timeout_seconds' => $requestTimeout,
            ]);

            throw new VoxelGenerationTimeoutException(
                'OpenAI tardó demasiado en responder. Prueba con imágenes más livianas o vuelve a intentarlo en unos segundos.',
                previous: $exception,
            );
        } catch (VoxelGenerationException $exception) {
            Log::warning('inmersivo.voxel_generation.failed', [
                'model' => $settings->model(),
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw new VoxelGenerationException('No se pudo generar la definición: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function extendExecutionWindow(int $httpTimeoutSeconds): void
    {
        if (! function_exists('set_time_limit')) {
            return;
        }

        $executionWindow = max(60, $httpTimeoutSeconds + 15);

        @set_time_limit($executionWindow);
    }

    /**
     * @param  array<string, string>  $imagePaths
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $previousDefinition
     * @return array<int, array<string, mixed>>
     */
    private function buildContent(array $imagePaths, string $instructions, array $context, ?array $previousDefinition, int $maxBoxes): array
    {
        $content = [['type' => 'input_text', 'text' => $this->userMessage($instructions, $context, $previousDefinition, $maxBoxes)]];

        foreach ($imagePaths as $view => $path) {
            $label = self::VIEW_LABELS[$view] ?? self::FALLBACK_VIEW_LABEL;

            $content[] = ['type' => 'input_text', 'text' => $label['title']."\n".$label['hint']];
            $content[] = ['type' => 'input_image', 'image_url' => $this->encodeImage($path), 'detail' => $label['detail']];
        }

        return $content;
    }

    /**
     * Mensaje dinámico por llamada — corto a propósito: las reglas
     * generales de reconstrucción (silueta, proporciones, huecos, límite de
     * cajas, tipos de forma) viven en `systemPrompt()` y no se repiten aquí.
     *
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>|null  $previousDefinition
     */
    private function userMessage(string $instructions, array $context, ?array $previousDefinition, int $maxBoxes): string
    {
        $normalizedInstructions = trim($instructions) !== ''
            ? trim($instructions)
            : 'Sin instrucciones extra: genera una aproximación fiel priorizando silueta y proporciones.';

        $text = 'Reconstruye en voxel el objeto mostrado en las referencias.'
            ."\n\nInstrucciones específicas:\n{$normalizedInstructions}"
            ."\n\nBusca la mayor similitud visual posible sin utilizar geometría innecesaria."
            ."\n\nPreserva especialmente:"
            ."\n- silueta"
            ."\n- proporciones"
            ."\n- partes distintivas"
            ."\n- distribución tridimensional"
            ."\n\nMáximo: {$maxBoxes} cajas.";

        if ($context !== []) {
            $text .= "\n\nContexto de la plantilla:\n".json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        if ($previousDefinition !== null) {
            $text .= "\n\nEsto es un REFINAMIENTO. La siguiente es la definición actual — trátala como punto de "
                .'partida, no como una plantilla fija: puedes modificar, eliminar, subdividir o añadir cajas para '
                .'aplicar las instrucciones y mejorar la fidelidad, sin limitarte a ajustar dimensiones. No '
                .'dupliques cajas innecesariamente solo por tener presupuesto disponible. Devuelve siempre la '
                ."definición COMPLETA resultante, nunca un diff:\n"
                .json_encode($previousDefinition, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return $text;
    }

    /**
     * La API rechaza (400) `reasoning`/`temperature` en modelos que no son
     * de razonamiento, y los rechaza igual de duro en los que sí lo son si
     * se omiten mal — hay que enviarlos condicionalmente, nunca "probar y
     * ver". Cobertura deliberadamente amplia (o1, o3, o4-mini, gpt-5, etc.)
     * porque la familia de razonamiento de OpenAI sigue creciendo.
     */
    private function isReasoningModel(?string $model): bool
    {
        return $model !== null && (bool) preg_match('/^(o\d|gpt-5)/i', $model);
    }

    private function encodeImage(string $relativePath): string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            throw new VoxelGenerationException("La imagen de referencia no existe: {$relativePath}");
        }

        $mime = $disk->mimeType($relativePath) ?: 'image/jpeg';
        $contents = $disk->get($relativePath);

        if ($contents === null) {
            throw new VoxelGenerationException("No se pudo leer la imagen de referencia: {$relativePath}");
        }

        [$mime, $contents] = $this->optimizeImageForVision($mime, $contents);

        return "data:{$mime};base64,".base64_encode($contents);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function optimizeImageForVision(string $mime, string $contents): array
    {
        if (! str_starts_with($mime, 'image/')) {
            return [$mime, $contents];
        }

        try {
            $image = (new ImageManager(Driver::class))->decode($contents);
            $image->orient();
            $image->scaleDown(width: self::MAX_IMAGE_WIDTH);

            return [
                'image/jpeg',
                (string) $image->encode(new JpegEncoder(72, progressive: true, strip: true)),
            ];
        } catch (Throwable) {
            return [$mime, $contents];
        }
    }

    /**
     * @param  array<int, string>  $allowedTextures
     */
    private function systemPrompt(int $maxBoxes, array $allowedTextures): string
    {
        $textureList = implode(', ', $allowedTextures);

        // Cuando el admin restringió "Colores permitidos", $allowedTextures
        // ya viene reducido (VoxelPaletteMatcher) a las texturas más
        // parecidas a esos colores — aquí solo se lo hace explícito en
        // texto; la aplicación DURA real es el enum del schema
        // (`jsonSchema()`), que la API no permite saltarse.
        $textureInstruction = count($allowedTextures) < count(VoxelDefinitionValidator::ALLOWED_TEXTURES)
            ? "PALETA RESTRINGIDA: el administrador pidió ceñirse a ciertos colores, así que usa EXCLUSIVAMENTE estas texturas (las más parecidas a esos colores): {$textureList}. No uses ninguna otra aunque encajara mejor visualmente. "
            : "Usa solo estos nombres de textura: {$textureList}. ";

        return 'Eres un generador de geometría voxel de baja poligonización para un motor Three.js. Reconstruye el '
            .'objeto mostrado en las imágenes de referencia usando exclusivamente cajas rectangulares ("boxes"). '
            .'No estás generando necesariamente edificios: las referencias pueden mostrar cualquier tipo de objeto '
            .'(construcción, vegetación, mobiliario urbano, monumento, vehículo sencillo, elemento decorativo, '
            .'etc.) — identifica primero qué clase de objeto es y ajusta tu estrategia de reconstrucción a partir '
            .'de ahí, sin cambiar el formato de salida. '
            .'OBJETIVO: máxima reconocibilidad visual con el menor número razonable de cajas. Ni el máximo detalle '
            .'posible ni el mínimo número de cajas posible — cada caja adicional solo se justifica si mejora '
            .'perceptiblemente la silueta, las proporciones, un cambio de volumen, una característica distintiva o '
            .'el perfil del objeto. El resultado debe verse claramente como ESE objeto de la referencia, no solo '
            .'como otro objeto de su misma categoría general. '
            .'PROCESO (interno, no lo describas en la respuesta): (1) identifica la clase general del objeto y su '
            .'silueta — ancho, profundidad, altura, forma general, asimetrías, partes sobresalientes; (2) '
            .'descompón el objeto en volumen principal, volúmenes secundarios y piezas distintivas; (3) clasifica '
            .'cada elemento en esencial (sin él el objeto deja de parecerse a la referencia), importante (mejora '
            .'mucho el reconocimiento) o decorativo (su ausencia apenas cambia la lectura general), y genera '
            .'esencial primero, importante después, y decorativo solo si sobra presupuesto de cajas. '
            .'ORDEN DE PRIORIDAD DE FIDELIDAD: silueta > proporciones > distribución tridimensional > partes '
            .'distintivas > cambios grandes de volumen > colores/materiales dominantes > detalles secundarios. '
            .'GUÍAS SEGÚN EL TIPO DE FORMA (aplícalas según lo que veas, no son categorías rígidas): usa cajas '
            .'grandes en superficies planas y continuas, sin subdividirlas sin necesidad; aproxima formas '
            .'inclinadas (techos, rampas, ramas, cubiertas) con escalones voxel cuando la inclinación importe para '
            .'la silueta; aproxima formas redondeadas (copas de árboles, cúpulas, arbustos, ruedas) con varios '
            .'niveles de cajas de distinto tamaño, sin buscar curvas perfectas — el resultado debe leerse como '
            .'estética voxel; conserva elementos delgados pero reconocibles (troncos, postes, patas, barandas, '
            .'soportes) aunque cuesten cajas; en elementos repetitivos (ventanas, ramas, listones, tejas, '
            .'columnas) no representes cada repetición — conserva el ritmo visual con las suficientes para que se '
            .'lea, sin gastar en ellas gran parte del presupuesto. '
            .'Para vegetación: conserva altura relativa, grosor/forma del tronco, punto de inicio de las ramas, '
            .'extensión y forma general de la copa, y su densidad visual — nunca la reduzcas a un tronco más una '
            .'caja de copa salvo que la referencia sea realmente así de simple. '
            .'Para construcciones: conserva la huella, alturas relativas, cuerpos principales, cubiertas, '
            .'salientes, torres si existen y el ritmo arquitectónico principal — no conviertas automáticamente '
            .'techos inclinados en losas planas, pero tampoco modeles cada teja. '
            .'Para objetos pequeños o mobiliario (bancas, faroles, stands, barricadas, señales): prioriza '
            .'estructura, patas/soportes, superficies principales y proporción; evita detalles microscópicos. '
            .'REGLA CRÍTICA DE CONTINUIDAD: nunca dejes huecos ni grietas visibles entre cajas que representan una '
            .'misma superficie continua (pared, techo, piso, cuerpo dividido en niveles) — las caras compartidas '
            .'entre cajas adyacentes deben coincidir exactamente o solaparse ligeramente (unos pocos centímetros), '
            .'nunca dejar un espacio entre ellas. Antes de responder, verifica mentalmente cada par de cajas '
            .'contiguas: si una termina en un valor de x/y/z, la siguiente debe empezar en ese mismo valor o antes '
            .'— nunca después. '
            .'LÍMITE DE CAJAS: nunca uses más de '.$maxBoxes.' cajas — es un TECHO, no un objetivo. Adapta la '
            .'cantidad real a la complejidad del objeto: uno simple puede necesitar solo una fracción pequeña del '
            .'límite; uno muy complejo puede acercarse a él. No gastes cajas que no mejoren perceptiblemente el '
            .'resultado. '
            .'Cada caja se define por posición (x,y,z) y tamaño (w,h,d) en metros, en coordenadas LOCALES a un '
            .'grupo cuyo origen es el centro de la huella del objeto sobre el suelo (y=0 es el piso), con eje '
            .'frontal +Z. Cada caja se define por su CENTRO y su tamaño total, no por una esquina: la cara derecha '
            .'de una caja está en x + w/2, la izquierda en x - w/2 (mismo patrón para y/h en vertical y z/d en '
            .'profundidad). '
            .$textureInstruction.'No inventes texturas ni propiedades fuera del esquema. '
            .'Responde siempre con la definición completa del objeto, nunca con un diff parcial.';
    }

    /**
     * @param  array<int, string>  $allowedTextures
     * @return array<string, mixed>
     */
    private function jsonSchema(int $maxBoxes, array $allowedTextures): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'version' => ['type' => 'integer', 'enum' => [1]],
                'boxes' => [
                    'type' => 'array',
                    'maxItems' => $maxBoxes,
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'x' => ['type' => 'number'],
                            'y' => ['type' => 'number'],
                            'z' => ['type' => 'number'],
                            'w' => ['type' => 'number'],
                            'h' => ['type' => 'number'],
                            'd' => ['type' => 'number'],
                            // Aplicación DURA de "colores permitidos": la API
                            // (structured outputs, strict:true) garantiza que
                            // el modelo no puede emitir una textura fuera de
                            // este enum, sin depender de que "obedezca" el
                            // prompt.
                            'texture' => ['type' => 'string', 'enum' => $allowedTextures],
                            'rotationY' => ['type' => 'number'],
                            'collidable' => ['type' => 'boolean'],
                        ],
                        'required' => ['x', 'y', 'z', 'w', 'h', 'd', 'texture', 'rotationY', 'collidable'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            'required' => ['version', 'boxes'],
            'additionalProperties' => false,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function extractDefinition(array $payload): array
    {
        $text = $payload['output_text'] ?? null;

        if (! is_string($text) || blank(trim($text))) {
            foreach (($payload['output'] ?? []) as $outputItem) {
                foreach (($outputItem['content'] ?? []) as $contentItem) {
                    if (is_string($contentItem['text'] ?? null) && filled(trim($contentItem['text']))) {
                        $text = $contentItem['text'];
                        break 2;
                    }
                }
            }
        }

        if (! is_string($text) || blank(trim($text))) {
            throw new VoxelGenerationException('OpenAI no devolvió ningún contenido interpretable.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new VoxelGenerationException('OpenAI devolvió una respuesta que no es JSON válido.');
        }

        return $decoded;
    }
}
