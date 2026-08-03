<?php

namespace App\Http\Middleware;

use App\Support\Http\AgentDiscoveryLinks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddAgentDiscoveryHeaders
{
    public function __construct(
        private readonly AgentDiscoveryLinks $agentDiscoveryLinks,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Link', $this->agentDiscoveryLinks->headerValue());

        return $response;
    }
}
