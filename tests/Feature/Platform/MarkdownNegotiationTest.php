<?php

namespace Tests\Feature\Platform;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkdownNegotiationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_returns_markdown_when_requested_by_accept_header(): void
    {
        $response = $this->get(route('home'), [
            'Accept' => 'text/markdown, text/html;q=0.9',
        ]);

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('Vary', 'Accept')
            ->assertHeader('X-Markdown-Tokens')
            ->assertSee('title:', false);
    }
}
