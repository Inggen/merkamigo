<?php

namespace App\Domain\Discovery\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Platform\Models\PlatformKnowledgeDocument;
use App\Models\User;
use App\Support\Ai\Contracts\GeneratesAssistedText;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Responde preguntas del asistente general de Merkamigo (pedido del
 * usuario: personaje flotante en varias páginas del sitio) — a diferencia
 * de `AnswerBusinessChatQuestion`, no representa a un negocio puntual,
 * sino a la plataforma en general. Nunca inventa datos de un negocio
 * específico (precio, horario, stock) porque no tiene ese contexto —
 * para eso remite a buscar o a la vitrina del negocio, que sí tiene su
 * propio chatbot con datos reales.
 *
 * También puede tomar acción, no solo responder texto (pedido del
 * usuario): redirigir a una búsqueda ya filtrada por categoría cuando el
 * visitante describe lo que busca ("jabones" → Belleza y cuidado
 * personal), armar una solicitud de "Pídelo en Merkamigo" a partir de la
 * conversación, o —en modo "emprendedor", dentro del panel— navegar a
 * una sección del panel o armar el primer paso de "Mi Merkamigo en cinco
 * minutos". Ninguna de estas acciones publica ni crea nada por su cuenta:
 * siempre lleva al formulario real, con los datos ya listos para que la
 * persona revise y confirme.
 */
class AnswerPlatformChatQuestion
{
    public const GENERAL = 'general';

    public const EMPRENDEDOR = 'emprendedor';

    /**
     * Secciones del panel de emprendedor a las que el asistente puede
     * navegar en modo `EMPRENDEDOR` — allowlist explícita, nunca una URL
     * libre que proponga el modelo, y solo tienen sentido si el negocio
     * ya existe (por eso no incluye "crear_vitrina", que es su propia
     * acción y no depende de tener negocio).
     */
    private const EMPRENDEDOR_DESTINATIONS = [
        'vitrina' => 'emprendedores.negocios.vitrina',
        'productos' => 'emprendedores.negocios.productos',
        'colaboradores' => 'emprendedores.negocios.colaboradores',
        'metricas' => 'emprendedores.negocios.metricas',
        'copiloto' => 'emprendedores.negocios.copiloto',
        'oportunidades' => 'emprendedores.negocios.oportunidades',
        'verificacion' => 'emprendedores.negocios.verificacion',
        'plan' => 'emprendedores.negocios.plan',
        'chatbot' => 'emprendedores.negocios.chatbot',
        'impulsar' => 'emprendedores.negocios.impulsar',
    ];

    public function __construct(
        private readonly GeneratesAssistedText $assistedText,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history  Últimos turnos de la conversación, más antiguo primero.
     * @return array{answer: ?string, action: ?array{label: string, url: string}}
     */
    public function handle(
        string $question,
        array $history = [],
        ?User $user = null,
        string $mode = self::GENERAL,
        ?string $currentPage = null,
        ?string $currentStep = null,
    ): array {
        $categories = Category::where('is_active', true)->orderBy('position')->get(['name', 'slug']);
        $municipalities = Municipality::where('is_active', true)->orderBy('name')->get(['name', 'slug']);
        $business = $mode === self::EMPRENDEDOR ? $user?->businesses()->first() : null;

        $raw = $this->assistedText->generate(
            $this->prompt($mode, $business),
            [
                'pregunta_actual' => $question,
                'conversacion_previa' => $history,
                'pagina_actual' => $currentPage,
                'paso_actual' => $currentStep,
                'como_funciona' => [
                    'para_emprendedores' => [
                        'Crea tu vitrina en cinco minutos: nombre, descripción, fotos y WhatsApp.',
                        'Agrega tus productos o servicios.',
                        'Publica y comparte tu enlace o código QR.',
                        'Recibe contactos directo por WhatsApp.',
                    ],
                    'para_compradores' => [
                        'Explora la plaza de tu municipio, sin necesidad de crear cuenta.',
                        'Encuentra negocios y productos cerca de ti.',
                        'Contacta directo por WhatsApp.',
                        'Si no encuentras lo que buscas, puede publicar una solicitud en "Pídelo en Merkamigo" y los negocios interesados le proponen.',
                    ],
                    'nota' => 'Merkamigo no procesa pagos ni domicilios: conecta compradores y negocios, el acuerdo lo hacen directamente por WhatsApp.',
                ],
                'categorias' => $categories->map(fn ($category) => ['nombre' => $category->name, 'slug' => $category->slug]),
                'municipios' => $municipalities->map(fn ($municipality) => ['nombre' => $municipality->name, 'slug' => $municipality->slug]),
                'preguntas_frecuentes' => config('faq.preguntas'),
                'documento_de_referencia' => PlatformKnowledgeDocument::current()->document_text,
                'negocio_del_emprendedor' => $business ? ['nombre' => $business->name, 'tiene_negocio' => true] : ['tiene_negocio' => false],
            ],
        );

        return $this->parse($raw, $categories, $municipalities, $mode, $business);
    }

    private function prompt(string $mode, ?Business $business): string
    {
        $base =
            'Eres el asistente general de Merkamigo, un marketplace que conecta negocios locales con compradores '.
            'en Colombia. Respondes en el chat de la plataforma — no representas a ningún negocio en particular, '.
            'representas a la plataforma. Responde en español, con un tono cercano y natural (como un mensaje de '.
            'WhatsApp bien escrito), sin sonar robótico. '.
            'Responde puntualmente SOLO lo que te preguntan — si te saludan o escriben algo breve ("hola", '.
            '"buenas"), responde solo con un saludo corto (puedes preguntar en qué ayudas), sin adelantarte a '.
            'explicar todo de una vez. No hay un límite fijo de frases, pero por defecto una respuesta corta y al '.
            'grano es mejor que una larga. '.
            'Usa exclusivamente los datos reales entregados en "como_funciona", "categorias", "municipios", '.
            '"preguntas_frecuentes" y, si viene, "documento_de_referencia" (un resumen más completo de las '.
            'funcionalidades de Merkamigo que un admin puede mantener actualizado, puede venir null). No inventes '.
            'categorías, municipios, negocios, precios ni condiciones que no estén ahí. Ten en cuenta '.
            '"conversacion_previa" para no repetirte ni perder el hilo. '.
            '"pagina_actual" (y, si aplica, "paso_actual") te dicen en qué parte de Merkamigo está la persona '.
            'ahora mismo — úsalo para dar ayuda situada sin que tenga que explicarte dónde está: por ejemplo, si '.
            '"paso_actual" es "Información" dentro del asistente de crear vitrina, y pregunta algo relacionado, '.
            'puedes explicarle directamente qué significa el campo de categoría en ESE formulario, sin que tenga '.
            'que preguntar "¿en qué página estoy?".'.
            "\n\n".
            'Además de responder, puedes proponer UNA acción — pero "accion": null es el caso normal y más '.
            'frecuente, no la excepción. Ponla SOLO cuando la persona está pidiendo explícitamente ir a algo o '.
            'hacer algo concreto ahora mismo (buscar, publicar, navegar, crear su vitrina). Si solo está '.
            'preguntando información, pidiendo una explicación, o ayuda para llenar un campo (ej. "¿qué escribo '.
            'en nombre del negocio?", "¿qué significa esto?"), responde el texto y deja "accion": null — no le '.
            'pegues un botón de acción a cada respuesta solo porque el tema se relaciona con alguna sección o '.
            'formulario. Ejemplo: si preguntan "¿qué debo poner en el nombre del negocio?", contesta la duda y '.
            'NO agregues ninguna acción (ni "navegar" ni ninguna otra) — no pidieron ir a ningún lado. Responde '.
            'SIEMPRE con un único objeto JSON válido, sin texto antes ni después, exactamente con esta forma:'.
            "\n".
            '{"respuesta": "texto para mostrarle a la persona", "accion": null}';

        if ($mode === self::EMPRENDEDOR) {
            return $base."\n\n".$this->emprendedorPromptExtra($business);
        }

        return $base."\n\n".$this->generalPromptExtra();
    }

    private function generalPromptExtra(): string
    {
        return
            'Si preguntan por un negocio específico, un producto, un precio, un horario o algo que depende de un '.
            'negocio puntual, dilo con honestidad: no tienes esa información porque no es sobre un negocio en '.
            'particular — sugiere buscarlo (acción "buscar") o visitar su vitrina, que sí tiene su propio chat '.
            'con datos reales.'.
            "\n".
            'Cuando la persona describe algo que quiere encontrar (ej. "busco jabones", "necesito un plomero"), '.
            'identifica la categoría real más parecida de "categorias" (por significado, no solo por texto exacto '.
            '— "jabones" es "Belleza y cuidado personal", por ejemplo) y responde:'.
            "\n".
            '{"respuesta": "...", "accion": {"tipo": "buscar", "etiqueta": "texto corto para un botón, ej. '.
            '\'Ver Belleza y cuidado personal\'", "categoria_slug": "slug-real-de-categorias", "municipio_slug": '.
            'null, "busqueda_libre": null}}'.
            "\n".
            'Si ninguna categoría real encaja pero igual quieres ofrecer buscar, usa "categoria_slug": null y '.
            '"busqueda_libre" con las palabras clave. Si la persona menciona un municipio real de "municipios", '.
            'inclúyelo en "municipio_slug".'.
            "\n".
            'Cuando la persona quiera pedir algo que no encuentra y ya tengas claro QUÉ necesita (después de '.
            'preguntar lo necesario si hace falta — no lo hagas en el primer mensaje sin contexto), ofrécele '.
            'publicarlo en "Pídelo en Merkamigo" con esta acción — nunca la publicas tú, solo la llevas al '.
            'formulario con los datos ya listos para que ella revise y confirme:'.
            "\n".
            '{"respuesta": "...", "accion": {"tipo": "pedido", "etiqueta": "Publicar mi solicitud", "titulo": '.
            '"título corto y claro de lo que necesita", "descripcion": "descripción con el detalle que haya dado '.
            'la persona", "categoria_slug": "slug real si aplica, si no null"}}'.
            "\n".
            'Nunca agregues explicaciones fuera del JSON, ni uses bloques de código (```). El campo "respuesta" '.
            'es lo único que la persona lee — la acción se muestra aparte como un botón.';
    }

    private function emprendedorPromptExtra(?Business $business): string
    {
        $destinations = implode(', ', array_keys(self::EMPRENDEDOR_DESTINATIONS));

        $extra =
            'Estás dentro del panel de un emprendedor (dueño de un negocio en Merkamigo), no en la página '.
            'pública — ayúdalo a usar SU panel: dónde editar su vitrina, agregar productos, ver métricas, '.
            'invitar colaboradores, promocionarse por WhatsApp, verificar su negocio, gestionar su plan o '.
            'configurar su chatbot IA. Usa "negocio_del_emprendedor" para saber si ya tiene un negocio creado, y '.
            '"pagina_actual" para saber en qué parte del panel está ahora mismo — si el nombre de la página '.
            'coincide con algo que pueda explicar (por ejemplo, en qué paso del formulario está y qué significa '.
            'cada campo), dalo por hecho sin que tenga que decírtelo.'.
            "\n".
            'Cuando la persona quiera ir a una sección concreta de SU panel PARA ADMINISTRAR SU NEGOCIO ACTUAL '.
            '(editar su vitrina existente, ver sus productos, sus métricas, etc.) y ya tenga negocio, responde '.
            'con:'.
            "\n".
            '{"respuesta": "...", "accion": {"tipo": "navegar", "etiqueta": "texto corto, ej. \'Ir a productos\'", '.
            '"destino": "una de estas claves exactas: '.$destinations.'"}}'.
            "\n".
            'También puedes ofrecer la acción "buscar" y "pedido" del asistente general si aplican (por ejemplo, '.
            'si pregunta algo que en realidad es sobre comprar, no sobre su propio negocio).'.
            "\n\n".
            'Importante: en Merkamigo una misma cuenta puede tener VARIOS negocios/vitrinas — así que "crear '.
            'vitrina", "crear otra vitrina" o "nuevo negocio" es SIEMPRE la acción "crear_vitrina" de abajo, '.
            'nunca "navegar" a "vitrina" (esa es para editar el negocio que ya tiene, no para crear uno nuevo), '.
            'sin importar si "negocio_del_emprendedor.tiene_negocio" ya es true. Guíalo con preguntas breves (una '.
            'o dos por mensaje, nunca todas de una vez) para reunir: nombre del negocio, categoría (de '.
            '"categorias"), municipio (de "municipios"), número de WhatsApp, y una descripción corta. Cuando ya '.
            'tengas al menos el nombre, ofrécele armar el primer paso con esta acción — nunca creas el negocio '.
            'tú, solo lo llevas al asistente real ("Mi Merkamigo en cinco minutos") con lo ya reunido, para que '.
            'confirme y siga desde ahí:'.
            "\n".
            '{"respuesta": "...", "accion": {"tipo": "crear_vitrina", "etiqueta": "Crear mi vitrina", "nombre": '.
            '"...", "whatsapp": "número tal cual lo dio, o null", "descripcion": "... o null", "categoria_slug": '.
            '"slug real o null", "municipio_slug": "slug real o null"}}';

        return $extra;
    }

    /**
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Municipality>  $municipalities
     * @return array{answer: ?string, action: ?array{label: string, url: string}}
     */
    private function parse(?string $raw, $categories, $municipalities, string $mode, ?Business $business): array
    {
        if ($raw === null) {
            return ['answer' => null, 'action' => null];
        }

        $decoded = json_decode($this->stripCodeFence($raw), true);

        if (! is_array($decoded) || ! isset($decoded['respuesta']) || ! is_string($decoded['respuesta'])) {
            // El modelo no devolvió el JSON esperado — se degrada a mostrar
            // el texto crudo como respuesta, sin acción, en vez de fallar.
            return ['answer' => $raw, 'action' => null];
        }

        $answer = $decoded['respuesta'];
        $action = is_array($decoded['accion'] ?? null) ? $decoded['accion'] : null;

        return ['answer' => $answer, 'action' => $this->buildAction($action, $categories, $municipalities, $mode, $business)];
    }

    private function stripCodeFence(string $raw): string
    {
        $raw = trim($raw);

        return preg_replace('/^```(?:json)?|```$/m', '', $raw) ?? $raw;
    }

    /**
     * @param  array<string, mixed>|null  $action
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, Municipality>  $municipalities
     * @return array{label: string, url: string}|null
     */
    private function buildAction(?array $action, $categories, $municipalities, string $mode, ?Business $business): ?array
    {
        if (! $action || ! is_string($action['tipo'] ?? null)) {
            return null;
        }

        $label = is_string($action['label'] ?? $action['etiqueta'] ?? null) ? ($action['etiqueta'] ?? $action['label']) : null;
        $categorySlug = $categories->pluck('slug')->contains($action['categoria_slug'] ?? null) ? $action['categoria_slug'] : null;
        $municipioSlug = $municipalities->pluck('slug')->contains($action['municipio_slug'] ?? null) ? $action['municipio_slug'] : null;

        if ($action['tipo'] === 'buscar') {
            return $this->buscarAction($action, $label, $categorySlug, $municipioSlug);
        }

        if ($action['tipo'] === 'pedido') {
            return $this->pedidoAction($action, $label, $categorySlug);
        }

        if ($mode === self::EMPRENDEDOR && $action['tipo'] === 'navegar' && $business) {
            return $this->navegarAction($action, $label, $business);
        }

        if ($mode === self::EMPRENDEDOR && $action['tipo'] === 'crear_vitrina') {
            // Una misma cuenta puede tener varios negocios (ver
            // preguntas_frecuentes) — "crear vitrina" aplica exista o no
            // ya un negocio, a diferencia de "navegar" que sí depende de
            // tener uno.
            return $this->crearVitrinaAction($action, $label, $categorySlug, $municipioSlug);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array{label: string, url: string}|null
     */
    private function buscarAction(array $action, ?string $label, ?string $categorySlug, ?string $municipioSlug): ?array
    {
        $freeText = is_string($action['busqueda_libre'] ?? null) ? trim($action['busqueda_libre']) : '';

        if (! $categorySlug && $freeText === '') {
            return null;
        }

        return [
            'label' => $label ?: __('Buscar'),
            // `municipio` siempre va explícito (con "todos" si no aplica
            // ninguno): la ruta es `/plaza/{municipio?}/{categoria?}`, y
            // omitir el primer segmento opcional mientras se llena el
            // segundo genera una URL rota con doble slash.
            'url' => route('buscar', array_filter([
                'municipio' => $municipioSlug ?: 'todos',
                'categoria' => $categorySlug,
                'q' => $freeText !== '' ? $freeText : null,
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array{label: string, url: string}|null
     */
    private function pedidoAction(array $action, ?string $label, ?string $categorySlug): ?array
    {
        $title = is_string($action['titulo'] ?? null) ? trim($action['titulo']) : '';

        if ($title === '') {
            return null;
        }

        return [
            'label' => $label ?: __('Publicar mi solicitud'),
            'url' => route('pidelo.nueva', array_filter([
                'titulo' => Str::limit($title, 120, ''),
                'descripcion' => is_string($action['descripcion'] ?? null) ? Str::limit(trim($action['descripcion']), 1000, '') : null,
                'categoria' => $categorySlug,
            ])),
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array{label: string, url: string}|null
     */
    private function navegarAction(array $action, ?string $label, Business $business): ?array
    {
        $destination = is_string($action['destino'] ?? null) ? $action['destino'] : null;
        $routeName = self::EMPRENDEDOR_DESTINATIONS[$destination] ?? null;

        if (! $routeName) {
            return null;
        }

        return [
            'label' => $label ?: __('Ir'),
            'url' => route($routeName, $business),
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array{label: string, url: string}|null
     */
    private function crearVitrinaAction(array $action, ?string $label, ?string $categorySlug, ?string $municipioSlug): ?array
    {
        $name = is_string($action['nombre'] ?? null) ? trim($action['nombre']) : '';

        if ($name === '') {
            return null;
        }

        return [
            'label' => $label ?: __('Crear mi vitrina'),
            'url' => route('emprendedores.crear-vitrina', array_filter([
                'nombre' => Str::limit($name, 255, ''),
                'whatsapp' => is_string($action['whatsapp'] ?? null) ? trim($action['whatsapp']) : null,
                'descripcion' => is_string($action['descripcion'] ?? null) ? Str::limit(trim($action['descripcion']), 1000, '') : null,
                'categoria' => $categorySlug,
                'municipio' => $municipioSlug,
            ])),
        ];
    }
}
