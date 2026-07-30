<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Storefronts\Models\Product;
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

            Category::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->get()
                ->each(function (Category $category) use (&$urls, $municipality) {
                    $urls[] = $this->url(route('plaza.category', [$municipality, $category]));
                });
        }

        Business::query()
            ->where('status', 'publicado')
            ->with(['storefront', 'products.media'])
            ->select(['id', 'slug', 'updated_at', 'logo_path'])
            ->chunk(200, function ($businesses) use (&$urls) {
                foreach ($businesses as $business) { /** @var Business $business */
                    $images = collect([
                        $business->logoUrl(),
                        $business->storefront?->coverUrl(),
                    ])->filter()->values()->all();

                    $business->products->each(function (Product $product) use (&$images) {
                        $product->media->each(fn ($media) => $images[] = $media->url());
                    });

                    $urls[] = $this->url(
                        route('vitrinas.show', $business),
                        $business->updated_at?->toAtomString(),
                        array_values(array_unique(array_filter($images))),
                    );

                    $business->products
                        ->where('status', 'publicado')
                        ->each(function (Product $product) use (&$urls, $business) {
                            $urls[] = $this->url(
                                route('vitrinas.product', [$business, $product]),
                                $product->updated_at?->toAtomString(),
                                $product->media->map(fn ($media) => $media->url())->values()->all(),
                            );
                        });
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
            $this->url(route('buscar')),
            $this->url(route('preguntas-frecuentes')),
            $this->url(route('soporte')),
            $this->url(route('emprendedores.bienvenida')),
            $this->url(route('labs.zipa-inmersiva')),
            $this->url(route('labs.cajica-inmersiva')),
            $this->url(route('terminos')),
            $this->url(route('privacidad')),
            $this->url(route('reglas-comunidad')),
            ...Category::query()
                ->where('is_active', true)
                ->orderBy('position')
                ->get()
                ->map(fn (Category $category) => $this->url(route('categorias.show', $category)))
                ->all(),
        ];
    }

    /**
     * @return array{loc: string, lastmod: ?string, images: array<int, string>}
     */
    private function url(string $loc, ?string $lastmod = null, array $images = []): array
    {
        return ['loc' => $loc, 'lastmod' => $lastmod, 'images' => $images];
    }

    /**
     * @param  array<int, array{loc: string, lastmod: ?string, images: array<int, string>}>  $urls
     */
    private function toXml(array $urls): string
    {
        $body = '';

        foreach ($urls as $url) {
            $body .= '<url><loc>'.e($url['loc']).'</loc>';

            if (! empty($url['lastmod'])) {
                $body .= '<lastmod>'.e($url['lastmod']).'</lastmod>';
            }

            foreach ($url['images'] as $image) {
                $body .= '<image:image><image:loc>'.e($image).'</image:loc></image:image>';
            }

            $body .= '</url>';
        }

        return '<'.'?xml version="1.0" encoding="UTF-8"?'.'>'
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'.$body.'</urlset>';
    }
}
