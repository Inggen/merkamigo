<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Municipality;
use Illuminate\Http\Response;

/**
 * Sitemap XML básico (1.1 del TODO): páginas estáticas, municipios,
 * categorías y vitrinas publicadas. Sin prioridades/frecuencias afinadas
 * todavía — es un punto de partida indexable, no una estrategia de SEO
 * completa.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = $this->staticUrls();

        foreach (Municipality::where('is_active', true)->get() as $municipality) {
            $urls[] = $this->url(route('plaza.show', $municipality));
        }

        Business::query()
            ->where('status', 'publicado')
            ->select(['id', 'slug', 'updated_at'])
            ->chunk(200, function ($businesses) use (&$urls) {
                foreach ($businesses as $business) {
                    $urls[] = $this->url(route('vitrinas.show', $business), $business->updated_at?->toAtomString());
                }
            });

        $xml = $this->toXml(collect($urls)->unique('loc')->values()->all());

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    private function staticUrls(): array
    {
        return [
            $this->url(route('home')),
            $this->url(route('como-funciona')),
            $this->url(route('municipios')),
            $this->url(route('categorias')),
            $this->url(route('preguntas-frecuentes')),
            $this->url(route('soporte')),
            $this->url(route('terminos')),
            $this->url(route('privacidad')),
            $this->url(route('reglas-comunidad')),
        ];
    }

    /**
     * @return array{loc: string, lastmod: ?string}
     */
    private function url(string $loc, ?string $lastmod = null): array
    {
        return ['loc' => $loc, 'lastmod' => $lastmod];
    }

    /**
     * @param  array<int, array{loc: string, lastmod: ?string}>  $urls
     */
    private function toXml(array $urls): string
    {
        $body = '';

        foreach ($urls as $url) {
            $body .= '<url><loc>'.e($url['loc']).'</loc>';

            if (! empty($url['lastmod'])) {
                $body .= '<lastmod>'.e($url['lastmod']).'</lastmod>';
            }

            $body .= '</url>';
        }

        return '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.$body.'</urlset>';
    }
}
