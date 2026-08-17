<?php

namespace App\Support\Seo;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\Product;
use App\Domain\Trust\Models\Recommendation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SchemaBuilder
{
    public static function organization(): array
    {
        return self::clean([
            '@type' => 'Organization',
            '@id' => route('home').'#organization',
            'name' => config('app.name', 'Merkamigo'),
            'url' => route('home'),
            'logo' => asset('icons/icon-512.png'),
            'sameAs' => [],
        ]);
    }

    public static function website(): array
    {
        return self::clean([
            '@type' => 'WebSite',
            '@id' => route('home').'#website',
            'name' => config('app.name', 'Merkamigo'),
            'url' => route('home'),
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'publisher' => [
                '@id' => route('home').'#organization',
            ],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => route('buscar', ['municipio' => 'todos']).'?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ]);
    }

    public static function webPage(string $name, string $description, string $url, array $options = []): array
    {
        return self::clean(array_merge([
            '@type' => $options['type'] ?? 'WebPage',
            '@id' => $url.'#webpage',
            'name' => $name,
            'description' => $description,
            'url' => $url,
            'inLanguage' => str_replace('_', '-', app()->getLocale()),
            'isPartOf' => [
                '@id' => route('home').'#website',
            ],
            'primaryImageOfPage' => filled($options['image'] ?? null)
                ? self::imageObject($options['image'], $name)
                : null,
        ], Arr::except($options, ['type', 'image'])));
    }

    public static function breadcrumb(array $items): array
    {
        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index) => self::clean([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'] ?? null,
                ]))
                ->all(),
        ];
    }

    public static function itemList(array $items, ?string $name = null): array
    {
        return self::clean([
            '@type' => 'ItemList',
            'name' => $name,
            'numberOfItems' => count($items),
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index) => self::clean([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $item['url'] ?? null,
                    'name' => $item['name'] ?? null,
                    'image' => $item['image'] ?? null,
                ]))
                ->all(),
        ]);
    }

    public static function siteNavigation(array $items, ?string $name = null): array
    {
        return self::clean([
            '@type' => 'ItemList',
            'name' => $name ?? __('Secciones principales de Merkamigo'),
            'numberOfItems' => count($items),
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index) => self::clean([
                    '@type' => 'SiteNavigationElement',
                    'position' => $index + 1,
                    'name' => $item['name'] ?? null,
                    'description' => $item['description'] ?? null,
                    'url' => $item['url'] ?? null,
                ]))
                ->all(),
        ]);
    }

    public static function faqPage(array $faqs): array
    {
        return [
            '@type' => 'FAQPage',
            'mainEntity' => collect($faqs)->map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['pregunta'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['respuesta'],
                ],
            ])->all(),
        ];
    }

    public static function imageObject(string $url, ?string $caption = null): array
    {
        return self::clean([
            '@type' => 'ImageObject',
            'contentUrl' => $url,
            'url' => $url,
            'caption' => $caption,
        ]);
    }

    public static function localBusiness(Business $business, Collection $products): array
    {
        $images = collect([$business->storefront?->coverUrl(), $business->logoUrl()])
            ->filter()
            ->values()
            ->all();
        $storeUrl = route('vitrinas.show', $business);
        $publishedRecommendations = $business->publishedRecommendations();
        $reviews = $publishedRecommendations
            ->take(5)
            ->map(fn (Recommendation $recommendation) => self::review($recommendation, $business))
            ->all();
        $ratedRecommendations = $publishedRecommendations->whereNotNull('rating');

        return self::clean([
            '@type' => 'Store',
            '@id' => $storeUrl.'#store',
            'name' => $business->name,
            'description' => $business->storefront?->description,
            'url' => $storeUrl,
            'mainEntityOfPage' => $storeUrl,
            'image' => $images === [] ? null : $images,
            'logo' => $business->logoUrl(),
            'slogan' => $business->storefront?->headline,
            'telephone' => $business->whatsapp_number,
            'address' => self::postalAddress($business),
            'geo' => $business->hasCoordinates() ? [
                '@type' => 'GeoCoordinates',
                'latitude' => $business->latitude,
                'longitude' => $business->longitude,
            ] : null,
            'areaServed' => self::areaServed($business),
            'keywords' => collect([$business->category?->name, $business->municipality?->name, $business->zone])
                ->filter()
                ->implode(', '),
            'sameAs' => array_values(array_filter($business->social_links ?? [])),
            'openingHoursSpecification' => self::openingHours($business),
            'review' => $reviews === [] ? null : $reviews,
            'aggregateRating' => $ratedRecommendations->isEmpty() ? null : [
                '@type' => 'AggregateRating',
                'ratingValue' => round($ratedRecommendations->avg('rating'), 1),
                'reviewCount' => $ratedRecommendations->count(),
                'bestRating' => Recommendation::MAX_RATING,
                'worstRating' => Recommendation::MIN_RATING,
            ],
            'hasOfferCatalog' => $products->isEmpty() ? null : [
                '@type' => 'OfferCatalog',
                'name' => __('Catálogo de :business', ['business' => $business->name]),
                'itemListElement' => $products->take(20)->map(function (Product $product) use ($business) {
                    return [
                        '@type' => 'Offer',
                        'itemOffered' => [
                            '@type' => $product->type === 'servicio' ? 'Service' : 'Product',
                            'name' => $product->name,
                            'url' => route('vitrinas.product', [$business, $product]),
                        ],
                    ];
                })->all(),
            ],
        ]);
    }

    /**
     * Todos los municipios donde atiende el negocio (principal + los
     * adicionales, 0.2.2 del TODO), no solo el principal.
     *
     * @return array<int, array<string, string>>|null
     */
    private static function areaServed(Business $business): ?array
    {
        $names = collect([$business->municipality?->name])
            ->merge($business->municipalities->pluck('name'))
            ->filter()
            ->unique()
            ->values();

        if ($names->isEmpty()) {
            return null;
        }

        return $names->map(fn (string $name) => ['@type' => 'City', 'name' => $name])->all();
    }

    public static function commerceEntity(Product $product, Business $business): array
    {
        $images = $product->media->map(fn ($media) => $media->url())->values()->all();

        $schema = [
            '@type' => $product->type === 'servicio' ? 'Service' : 'Product',
            '@id' => route('vitrinas.product', [$business, $product]).'#product',
            'name' => $product->name,
            'description' => $product->description ?: $business->storefront?->description,
            'url' => route('vitrinas.product', [$business, $product]),
            'mainEntityOfPage' => route('vitrinas.product', [$business, $product]),
            'image' => $images === [] ? null : $images,
            'brand' => [
                '@type' => 'Brand',
                'name' => $business->name,
            ],
            'provider' => [
                '@id' => route('vitrinas.show', $business).'#store',
            ],
            'areaServed' => self::areaServed($business),
            'offers' => self::offer($product, $business),
        ];

        if ($product->type === 'producto') {
            $schema['category'] = $business->category?->name;
            $schema['sku'] = 'MKG-'.$business->id.'-'.$product->id;
        }

        return self::clean($schema);
    }

    public static function review(Recommendation $recommendation, Business $business): array
    {
        return self::clean([
            '@type' => 'Review',
            'itemReviewed' => [
                '@id' => route('vitrinas.show', $business).'#store',
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $recommendation->authorUser?->name ?? __('Cliente verificado'),
            ],
            // Nulo para recomendaciones de antes de que existiera la
            // calificación (pedido del usuario) — `self::clean()` quita la
            // clave completa en ese caso, en vez de mandar un `reviewRating`
            // sin `ratingValue`.
            'reviewRating' => $recommendation->rating === null ? null : [
                '@type' => 'Rating',
                'ratingValue' => $recommendation->rating,
                'bestRating' => Recommendation::MAX_RATING,
                'worstRating' => Recommendation::MIN_RATING,
            ],
            'reviewBody' => $recommendation->body,
            'datePublished' => $recommendation->published_at?->toDateString(),
            'publisher' => [
                '@id' => route('home').'#organization',
            ],
            'positiveNotes' => blank($recommendation->tags)
                ? null
                : [
                    '@type' => 'ItemList',
                    'itemListElement' => collect($recommendation->tags)
                        ->filter()
                        ->values()
                        ->map(fn (string $tag, int $index) => [
                            '@type' => 'ListItem',
                            'position' => $index + 1,
                            'name' => $tag,
                        ])->all(),
                ],
        ]);
    }

    public static function contactPage(?string $whatsapp = null): array
    {
        return self::clean([
            '@type' => 'ContactPage',
            'name' => __('Soporte de Merkamigo'),
            'url' => route('soporte'),
            'mainEntity' => filled($whatsapp) ? [
                '@type' => 'ContactPoint',
                'contactType' => 'customer support',
                'telephone' => $whatsapp,
                'url' => 'https://wa.me/'.preg_replace('/\D/', '', $whatsapp),
                'areaServed' => 'CO',
                'availableLanguage' => ['es'],
            ] : null,
        ]);
    }

    private static function offer(Product $product, Business $business): ?array
    {
        $price = $product->hasActivePromo() ? $product->promo_price : $product->price;

        if (! filled($price) || $product->price_type === 'consultar') {
            return self::clean([
                '@type' => 'Offer',
                '@id' => route('vitrinas.product', [$business, $product]).'#offer',
                'url' => route('vitrinas.product', [$business, $product]),
                'priceCurrency' => 'COP',
                'availability' => $product->isSoldOut()
                    ? 'https://schema.org/OutOfStock'
                    : 'https://schema.org/InStock',
                'seller' => [
                    '@id' => route('vitrinas.show', $business).'#store',
                ],
            ]);
        }

        $offer = [
            '@type' => 'Offer',
            '@id' => route('vitrinas.product', [$business, $product]).'#offer',
            'url' => route('vitrinas.product', [$business, $product]),
            'priceCurrency' => 'COP',
            'price' => number_format((float) $price, 2, '.', ''),
            'availability' => $product->isSoldOut()
                ? 'https://schema.org/OutOfStock'
                : 'https://schema.org/InStock',
            'seller' => [
                '@id' => route('vitrinas.show', $business).'#store',
            ],
        ];

        if ($product->hasActivePromo() && filled($product->price) && (float) $product->promo_price < (float) $product->price) {
            $offer['priceSpecification'] = [
                [
                    '@type' => 'UnitPriceSpecification',
                    'price' => number_format((float) $product->price, 2, '.', ''),
                    'priceCurrency' => 'COP',
                    'priceType' => 'https://schema.org/StrikethroughPrice',
                ],
            ];

            if ($product->promo_ends_at) {
                $offer['priceValidUntil'] = $product->promo_ends_at->toDateString();
            }
        }

        return self::clean($offer);
    }

    private static function postalAddress(Business $business): ?array
    {
        if (! $business->municipality && blank($business->address)) {
            return null;
        }

        return self::clean([
            '@type' => 'PostalAddress',
            'streetAddress' => $business->address,
            'addressLocality' => $business->municipality?->name,
            'addressRegion' => $business->municipality?->department,
            'addressCountry' => 'CO',
        ]);
    }

    private static function openingHours(Business $business): ?array
    {
        if (! $business->hasStructuredSchedule()) {
            return null;
        }

        $map = [
            'monday' => 'https://schema.org/Monday',
            'tuesday' => 'https://schema.org/Tuesday',
            'wednesday' => 'https://schema.org/Wednesday',
            'thursday' => 'https://schema.org/Thursday',
            'friday' => 'https://schema.org/Friday',
            'saturday' => 'https://schema.org/Saturday',
            'sunday' => 'https://schema.org/Sunday',
        ];

        $specs = [];

        foreach (($business->hours['schedule'] ?? []) as $day => $data) {
            if (($data['closed'] ?? false) || blank($data['open'] ?? null) || blank($data['close'] ?? null)) {
                continue;
            }

            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $map[$day] ?? null,
                'opens' => $data['open'],
                'closes' => $data['close'],
            ];
        }

        return $specs === [] ? null : $specs;
    }

    private static function clean(array $data): array
    {
        return collect($data)
            ->map(function ($value) {
                if (is_array($value)) {
                    return self::clean($value);
                }

                return $value;
            })
            ->reject(function ($value) {
                if (is_array($value)) {
                    return $value === [];
                }

                return $value === null || $value === '';
            })
            ->all();
    }
}
