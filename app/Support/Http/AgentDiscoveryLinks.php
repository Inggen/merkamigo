<?php

namespace App\Support\Http;

class AgentDiscoveryLinks
{
    /**
     * @return array<int, array{href: string, rel: string, type: string}>
     */
    public function entries(): array
    {
        return [
            [
                'href' => route('well-known.api-catalog', absolute: false),
                'rel' => 'api-catalog',
                'type' => 'application/linkset+json',
            ],
            [
                'href' => route('docs.api', absolute: false),
                'rel' => 'service-desc',
                'type' => 'application/yaml',
            ],
            [
                'href' => route('docs.api.reference', absolute: false),
                'rel' => 'service-doc',
                'type' => 'text/html',
            ],
            [
                'href' => route('api.v1.health', absolute: false),
                'rel' => 'status',
                'type' => 'application/json',
            ],
        ];
    }

    public function headerValue(): string
    {
        return collect($this->entries())
            ->map(fn (array $link) => sprintf(
                '<%s>; rel="%s"; type="%s"',
                $link['href'],
                $link['rel'],
                $link['type'],
            ))
            ->implode(', ');
    }

    /**
     * @return array<string, mixed>
     */
    public function catalog(): array
    {
        return [
            'linkset' => [
                [
                    'anchor' => url('/api/v1'),
                    'service-desc' => [
                        [
                            'href' => url(route('docs.api', absolute: false)),
                            'type' => 'application/yaml',
                        ],
                    ],
                    'service-doc' => [
                        [
                            'href' => url(route('docs.api.reference', absolute: false)),
                            'type' => 'text/html',
                        ],
                    ],
                    'status' => [
                        [
                            'href' => url(route('api.v1.health', absolute: false)),
                            'type' => 'application/json',
                        ],
                    ],
                    'api-catalog' => [
                        [
                            'href' => url(route('well-known.api-catalog', absolute: false)),
                            'type' => 'application/linkset+json',
                        ],
                    ],
                    'describedby' => [
                        [
                            'href' => 'https://www.rfc-editor.org/info/rfc9727',
                            'type' => 'text/html',
                        ],
                    ],
                ],
            ],
        ];
    }
}
