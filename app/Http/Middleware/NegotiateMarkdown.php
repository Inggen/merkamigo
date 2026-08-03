<?php

namespace App\Http\Middleware;

use App\Support\Http\HtmlToMarkdown;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NegotiateMarkdown
{
    public function __construct(
        private readonly HtmlToMarkdown $htmlToMarkdown,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->wantsMarkdown($request) || ! $this->canTransform($request, $response)) {
            return $response;
        }

        $html = $response->getContent();

        if (! is_string($html) || trim($html) === '') {
            return $response;
        }

        $markdown = $this->htmlToMarkdown->convert($html);

        $response->setContent($request->isMethod('HEAD') ? '' : $markdown);
        $response->headers->set('Content-Type', 'text/markdown; charset=UTF-8');
        $response->headers->set('X-Markdown-Tokens', (string) $this->htmlToMarkdown->estimateTokens($markdown));
        $response->headers->set('X-Original-Tokens', (string) $this->htmlToMarkdown->estimateTokens(strip_tags($html)));

        $this->appendVaryAccept($response);

        foreach (['Content-Encoding', 'Content-Range', 'Transfer-Encoding', 'ETag', 'Last-Modified', 'Content-Length'] as $header) {
            $response->headers->remove($header);
        }

        return $response;
    }

    private function wantsMarkdown(Request $request): bool
    {
        $accept = strtolower((string) $request->headers->get('Accept'));

        return str_contains($accept, 'text/markdown');
    }

    private function canTransform(Request $request, Response $response): bool
    {
        if (! in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return false;
        }

        if ($response->isRedirection() || $response->isEmpty()) {
            return false;
        }

        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        return $contentType === ''
            || str_starts_with($contentType, 'text/html')
            || str_starts_with($contentType, 'application/xhtml+xml');
    }

    private function appendVaryAccept(Response $response): void
    {
        $vary = collect(explode(',', (string) $response->headers->get('Vary', '')))
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->push('Accept')
            ->unique(fn (string $value) => strtolower($value))
            ->implode(', ');

        $response->headers->set('Vary', $vary);
    }
}
