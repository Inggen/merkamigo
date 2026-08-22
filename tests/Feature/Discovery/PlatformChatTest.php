<?php

namespace Tests\Feature\Discovery;

use App\Domain\Discovery\Actions\AnswerPlatformChatQuestion;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Asistente general de Merkamigo (pedido del usuario: personaje flotante
 * fuera de vitrina/producto) — responde en texto y puede proponer una
 * acción real (buscar, publicar en Pídelo, navegar en el panel de
 * emprendedor, o armar el primer paso de crear vitrina), siempre
 * validada contra datos reales antes de convertirla en un enlace.
 */
class PlatformChatTest extends TestCase
{
    use RefreshDatabase;

    private function fakeAi(string $jsonResponse): void
    {
        $this->app->bind(GeneratesAssistedText::class, fn () => new class($jsonResponse) implements GeneratesAssistedText
        {
            public function __construct(private readonly string $response) {}

            public function generate(string $prompt, array $context = []): ?string
            {
                return $this->response;
            }
        });
    }

    private function category(string $name = 'Belleza y cuidado personal'): Category
    {
        return Category::create(['name' => $name, 'slug' => Str::slug($name), 'is_active' => true]);
    }

    public function test_answers_with_no_action_by_default(): void
    {
        $this->fakeAi(json_encode(['respuesta' => 'Hola, ¿en qué te ayudo?', 'accion' => null]));

        [$answer, $action] = array_values(app(AnswerPlatformChatQuestion::class)->handle('hola'));

        $this->assertSame('Hola, ¿en qué te ayudo?', $answer);
        $this->assertNull($action);
    }

    public function test_buscar_action_builds_a_valid_search_url_for_a_real_category(): void
    {
        $category = $this->category();
        $this->fakeAi(json_encode([
            'respuesta' => 'Te muestro esa categoría.',
            'accion' => ['tipo' => 'buscar', 'etiqueta' => 'Ver Belleza', 'categoria_slug' => $category->slug, 'municipio_slug' => null, 'busqueda_libre' => null],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('busco jabones');

        $this->assertSame('Ver Belleza', $result['action']['label']);
        $this->assertSame(route('buscar', ['municipio' => 'todos', 'categoria' => $category->slug]), $result['action']['url']);

        // La URL real no debe tener el bug de doble slash cuando falta el
        // municipio (route() con un parámetro opcional en medio vacío).
        $this->assertStringNotContainsString('//', parse_url($result['action']['url'], PHP_URL_PATH));
    }

    public function test_buscar_action_with_a_hallucinated_category_slug_is_dropped(): void
    {
        $this->fakeAi(json_encode([
            'respuesta' => 'Te muestro esa categoría.',
            'accion' => ['tipo' => 'buscar', 'etiqueta' => 'Ver', 'categoria_slug' => 'categoria-que-no-existe', 'municipio_slug' => null, 'busqueda_libre' => null],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('busco algo raro');

        $this->assertNull($result['action']);
    }

    public function test_buscar_action_falls_back_to_free_text_without_a_matching_category(): void
    {
        $this->fakeAi(json_encode([
            'respuesta' => 'No encontré una categoría exacta, te muestro resultados generales.',
            'accion' => ['tipo' => 'buscar', 'etiqueta' => 'Buscar "artesanías raras"', 'categoria_slug' => null, 'municipio_slug' => null, 'busqueda_libre' => 'artesanías raras'],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('busco artesanías raras');

        $this->assertSame(route('buscar', ['municipio' => 'todos', 'q' => 'artesanías raras']), $result['action']['url']);
    }

    public function test_pedido_action_builds_a_pidelo_link_with_the_gathered_data(): void
    {
        $category = $this->category('Plomería');
        $this->fakeAi(json_encode([
            'respuesta' => 'Te llevo a publicar tu solicitud.',
            'accion' => [
                'tipo' => 'pedido',
                'etiqueta' => 'Publicar mi solicitud',
                'titulo' => 'Necesito un plomero urgente',
                'descripcion' => 'Se dañó una tubería en la cocina.',
                'categoria_slug' => $category->slug,
            ],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('necesito un plomero urgente, se dañó una tubería');

        $expectedUrl = route('pidelo.nueva', [
            'titulo' => 'Necesito un plomero urgente',
            'descripcion' => 'Se dañó una tubería en la cocina.',
            'categoria' => $category->slug,
        ]);

        $this->assertSame($expectedUrl, $result['action']['url']);
    }

    public function test_pedido_action_without_a_title_is_dropped(): void
    {
        $this->fakeAi(json_encode([
            'respuesta' => '¿Qué necesitas exactamente?',
            'accion' => ['tipo' => 'pedido', 'etiqueta' => 'Publicar', 'titulo' => '', 'descripcion' => null, 'categoria_slug' => null],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('necesito algo');

        $this->assertNull($result['action']);
    }

    public function test_falls_back_to_raw_text_when_the_model_does_not_return_json(): void
    {
        $this->fakeAi('Esto no es JSON, es solo texto plano.');

        $result = app(AnswerPlatformChatQuestion::class)->handle('hola');

        $this->assertSame('Esto no es JSON, es solo texto plano.', $result['answer']);
        $this->assertNull($result['action']);
    }

    public function test_returns_null_answer_when_the_ai_is_disabled(): void
    {
        $this->app->bind(GeneratesAssistedText::class, fn () => new class implements GeneratesAssistedText
        {
            public function generate(string $prompt, array $context = []): ?string
            {
                return null;
            }
        });

        $result = app(AnswerPlatformChatQuestion::class)->handle('hola');

        $this->assertNull($result['answer']);
        $this->assertNull($result['action']);
    }

    public function test_emprendedor_mode_navegar_action_requires_an_existing_business(): void
    {
        $owner = User::factory()->create();
        $this->fakeAi(json_encode([
            'respuesta' => 'Aún no tienes un negocio creado.',
            'accion' => ['tipo' => 'navegar', 'etiqueta' => 'Ir a productos', 'destino' => 'productos'],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('llévame a mis productos', [], $owner, AnswerPlatformChatQuestion::EMPRENDEDOR);

        $this->assertNull($result['action']);
    }

    public function test_emprendedor_mode_navegar_action_points_to_the_right_dashboard_section(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Asistente'])->business;

        $this->fakeAi(json_encode([
            'respuesta' => 'Te llevo a tus productos.',
            'accion' => ['tipo' => 'navegar', 'etiqueta' => 'Ir a productos', 'destino' => 'productos'],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('llévame a mis productos', [], $owner, AnswerPlatformChatQuestion::EMPRENDEDOR);

        $this->assertSame(route('emprendedores.negocios.productos', $business), $result['action']['url']);
    }

    public function test_emprendedor_mode_ignores_an_unknown_navegar_destination(): void
    {
        $owner = User::factory()->create();
        app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Asistente 2'])->business;

        $this->fakeAi(json_encode([
            'respuesta' => 'Te llevo ahí.',
            'accion' => ['tipo' => 'navegar', 'etiqueta' => 'Ir', 'destino' => 'seccion-inventada'],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('llévame a algo raro', [], $owner, AnswerPlatformChatQuestion::EMPRENDEDOR);

        $this->assertNull($result['action']);
    }

    public function test_emprendedor_mode_crear_vitrina_action_works_without_an_existing_business(): void
    {
        $municipality = Municipality::create(['name' => 'Cajicá', 'slug' => 'cajica', 'department' => 'Cundinamarca', 'is_active' => true]);
        $category = $this->category('Alimentos y bebidas');
        $owner = User::factory()->create();

        $this->fakeAi(json_encode([
            'respuesta' => '¡Vamos a crear tu vitrina!',
            'accion' => [
                'tipo' => 'crear_vitrina',
                'etiqueta' => 'Crear mi vitrina',
                'nombre' => 'Panadería Doña Rosa',
                'whatsapp' => '3001234567',
                'descripcion' => 'Pan artesanal recién horneado.',
                'categoria_slug' => $category->slug,
                'municipio_slug' => $municipality->slug,
            ],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('quiero crear mi vitrina de panadería', [], $owner, AnswerPlatformChatQuestion::EMPRENDEDOR);

        $expectedUrl = route('emprendedores.crear-vitrina', [
            'nombre' => 'Panadería Doña Rosa',
            'whatsapp' => '3001234567',
            'descripcion' => 'Pan artesanal recién horneado.',
            'categoria' => $category->slug,
            'municipio' => $municipality->slug,
        ]);

        $this->assertSame($expectedUrl, $result['action']['url']);
    }

    /**
     * Bug real reportado por el usuario: pidió "crea otra vitrina" ya
     * teniendo un negocio, y el botón lo mandaba a editar el negocio que
     * ya tenía en vez de al formulario de crear uno nuevo — una misma
     * cuenta puede tener varios negocios (ver preguntas_frecuentes), así
     * que "crear_vitrina" debe seguir aplicando aunque ya haya uno.
     */
    public function test_emprendedor_mode_crear_vitrina_action_also_works_with_an_existing_business(): void
    {
        $owner = User::factory()->create();
        app(CreateStorefront::class)->handle($owner, ['name' => 'Mi Primer Negocio'])->business;

        $this->fakeAi(json_encode([
            'respuesta' => '¡Vamos a crear tu segunda vitrina!',
            'accion' => [
                'tipo' => 'crear_vitrina',
                'etiqueta' => 'Crear otra vitrina',
                'nombre' => 'Mi Segundo Negocio',
                'whatsapp' => null,
                'descripcion' => null,
                'categoria_slug' => null,
                'municipio_slug' => null,
            ],
        ]));

        $result = app(AnswerPlatformChatQuestion::class)->handle('crea otra vitrina', [], $owner, AnswerPlatformChatQuestion::EMPRENDEDOR);

        $this->assertNotNull($result['action']);
        $this->assertStringStartsWith(route('emprendedores.crear-vitrina'), $result['action']['url']);
        $this->assertStringContainsString('nombre=Mi%20Segundo%20Negocio', $result['action']['url']);
    }

    public function test_the_platform_chat_endpoint_accepts_a_mode_and_returns_answer_and_action(): void
    {
        $category = $this->category();
        $this->fakeAi(json_encode([
            'respuesta' => 'Aquí tienes.',
            'accion' => ['tipo' => 'buscar', 'etiqueta' => 'Ver', 'categoria_slug' => $category->slug, 'municipio_slug' => null, 'busqueda_libre' => null],
        ]));

        $this->postJson(route('api.v1.asistente.chat'), ['question' => 'busco jabones', 'mode' => 'general'])
            ->assertOk()
            ->assertJsonPath('data.answer', 'Aquí tienes.')
            ->assertJsonPath('data.action.url', route('buscar', ['municipio' => 'todos', 'categoria' => $category->slug]));
    }

    public function test_the_platform_chat_endpoint_resolves_the_authenticated_users_business_in_emprendedor_mode(): void
    {
        $owner = User::factory()->create();
        $business = app(CreateStorefront::class)->handle($owner, ['name' => 'Negocio Endpoint'])->business;

        $this->fakeAi(json_encode([
            'respuesta' => 'Te llevo a métricas.',
            'accion' => ['tipo' => 'navegar', 'etiqueta' => 'Ver métricas', 'destino' => 'metricas'],
        ]));

        $this->actingAs($owner)
            ->postJson(route('api.v1.asistente.chat'), ['question' => 'muéstrame mis métricas', 'mode' => 'emprendedor'])
            ->assertOk()
            ->assertJsonPath('data.action.url', route('emprendedores.negocios.metricas', $business));
    }

    public function test_the_platform_chat_endpoint_rejects_an_invalid_mode(): void
    {
        $this->postJson(route('api.v1.asistente.chat'), ['question' => 'hola', 'mode' => 'algo-invalido'])
            ->assertStatus(422);
    }

    /**
     * Pedido del usuario: el asistente debe saber en qué página/paso está
     * la persona para poder ayudarla con eso puntual (ej. explicar qué
     * significa el campo de categoría estando en el paso "Información"
     * del asistente de crear vitrina), sin que tenga que decírselo.
     */
    public function test_the_current_page_and_step_reach_the_ai_context(): void
    {
        $spy = new SpyGeneratesAssistedText;
        $this->app->instance(GeneratesAssistedText::class, $spy);

        app(AnswerPlatformChatQuestion::class)->handle(
            '¿qué debo poner aquí?',
            [],
            null,
            AnswerPlatformChatQuestion::EMPRENDEDOR,
            'emprendedores.crear-vitrina',
            'Información',
        );

        $this->assertSame('emprendedores.crear-vitrina', $spy->context['pagina_actual']);
        $this->assertSame('Información', $spy->context['paso_actual']);
    }
}

class SpyGeneratesAssistedText implements GeneratesAssistedText
{
    public ?string $prompt = null;

    /** @var array<string, mixed> */
    public array $context = [];

    public function generate(string $prompt, array $context = []): ?string
    {
        $this->prompt = $prompt;
        $this->context = $context;

        return json_encode(['respuesta' => 'Ok.', 'accion' => null]);
    }
}
