<?php

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentDiscoveryHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_advertises_agent_discovery_links(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader(
                'Link',
                '</.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json", </docs/api>; rel="service-desc"; type="application/yaml", </docs/api/reference>; rel="service-doc"; type="text/html", </api/v1/health>; rel="status"; type="application/json"',
            );
    }

    public function test_api_catalog_is_public_and_uses_rfc_9727_linkset_shape(): void
    {
        $this->get(route('well-known.api-catalog'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/linkset+json; profile="https://www.rfc-editor.org/info/rfc9727"')
            ->assertJsonPath('linkset.0.anchor', url('/api/v1'))
            ->assertJsonPath('linkset.0.service-desc.0.href', route('docs.api'))
            ->assertJsonPath('linkset.0.service-doc.0.href', route('docs.api.reference'))
            ->assertJsonPath('linkset.0.status.0.href', route('api.v1.health'));
    }
}
