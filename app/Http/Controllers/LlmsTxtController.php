<?php

namespace App\Http\Controllers;

use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use Illuminate\Http\Response;

/**
 * `/llms.txt` (optimización GEO, 2026-10-03): resumen del sitio en el
 * formato que ya usan varios crawlers de IA (GPTBot, ClaudeBot,
 * PerplexityBot...) para entender rápido de qué trata una plataforma, sin
 * tener que rastrear todo el sitio. Convención de llmstxt.org — no es un
 * estándar oficial de ningún motor, es una señal adicional, no un
 * reemplazo del SEO técnico normal (sitemap, schema.org, meta tags).
 *
 * Generado dinámicamente (no es un archivo estático en `public/`) para que
 * la lista de municipios y categorías activas nunca quede desactualizada,
 * mismo criterio que `SitemapController`.
 */
class LlmsTxtController extends Controller
{
    public function index(): Response
    {
        $municipalities = Municipality::where('is_active', true)->orderBy('name')->get();
        $categories = Category::where('is_active', true)->orderBy('position')->get();

        $lines = [
            '# Merkamigo',
            '',
            '> Merkamigo es una plataforma colombiana para descubrir negocios, productos y servicios locales. Cada negocio publica una vitrina digital gratuita en la Plaza de su municipio; compradores y negocios se contactan directo por WhatsApp. Merkamigo no procesa pagos ni entregas por defecto — el acuerdo lo hacen las dos partes directamente, salvo que el negocio active su propio cobro en línea.',
            '',
            '## Páginas principales',
            '',
            '- ['.__('Cómo funciona').']('.route('como-funciona').'): explica el flujo para compradores y para emprendedores.',
            '- ['.__('Planes y precios').']('.route('planes-y-precios').'): planes de suscripción para negocios (gratuito y de pago) y servicios puntuales.',
            '- ['.__('Preguntas frecuentes').']('.route('preguntas-frecuentes').')',
            '- ['.__('Municipios').']('.route('municipios').'): listado de municipios donde Merkamigo está activo.',
            '- ['.__('Categorías').']('.route('categorias').'): categorías de negocios disponibles.',
            '- ['.__('Buscar / Plaza').']('.route('buscar').'): buscador público de negocios, productos y solicitudes ("Pídelo en Merkamigo").',
            '',
            '## Municipios activos',
            '',
            ...$municipalities->map(fn (Municipality $m) => '- ['.$m->name.']('.route('buscar', ['municipio' => $m->slug]).')')->all(),
            '',
            '## Categorías activas',
            '',
            ...$categories->map(fn (Category $c) => '- ['.$c->name.']('.route('categorias.show', $c).')')->all(),
            '',
            '## Para desarrolladores y agentes',
            '',
            '- API pública y catálogo de endpoints: '.route('well-known.api-catalog'),
            '- Documentación OpenAPI: '.route('docs.api'),
            '',
            '## Notas',
            '',
            '- El contenido relevante de cada vitrina de negocio (nombre, descripción, productos, ubicación, horarios) está en HTML semántico con datos estructurados Schema.org (LocalBusiness/Product/Service) — no requiere JavaScript para leerse.',
            '- Los precios y la disponibilidad de productos cambian con frecuencia; usar siempre la página citada como fuente, no un valor memorizado.',
        ];

        return response(implode("\n", $lines), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
