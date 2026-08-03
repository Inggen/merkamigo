<?php

namespace App\Http\Controllers;

use App\Support\Http\AgentDiscoveryLinks;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AgentDiscoveryController extends Controller
{
    public function __construct(
        private readonly AgentDiscoveryLinks $agentDiscoveryLinks,
    ) {}

    public function catalog(): JsonResponse
    {
        return response()
            ->json($this->agentDiscoveryLinks->catalog())
            ->header('Content-Type', 'application/linkset+json; profile="https://www.rfc-editor.org/info/rfc9727"');
    }

    public function openApi(): BinaryFileResponse
    {
        return response()->file(
            base_path('docs/api/openapi.yaml'),
            ['Content-Type' => 'application/yaml; charset=UTF-8'],
        );
    }

    public function documentation(): View
    {
        return view('public.api-docs', [
            'openApiUrl' => route('docs.api'),
            'healthUrl' => route('api.v1.health'),
            'catalogUrl' => route('well-known.api-catalog'),
            'apiBaseUrl' => url('/api/v1'),
        ]);
    }
}
