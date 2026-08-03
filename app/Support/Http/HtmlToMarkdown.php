<?php

namespace App\Support\Http;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Illuminate\Support\Str;

class HtmlToMarkdown
{
    public function convert(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');

        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOERROR | LIBXML_NOWARNING);

        $this->removeNodes($document);

        $title = trim((string) $document->getElementsByTagName('title')->item(0)?->textContent);
        $body = $document->getElementsByTagName('main')->item(0)
            ?? $document->getElementsByTagName('article')->item(0)
            ?? $document->getElementsByTagName('body')->item(0);

        $markdown = $body instanceof DOMNode
            ? trim($this->renderChildren($body))
            : '';

        if ($title !== '') {
            $markdown = "---\ntitle: {$this->escapeFrontmatter($title)}\n---\n\n".$markdown;
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $markdown) ?? $markdown);
    }

    public function estimateTokens(string $content): int
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($content)) ?? '';

        if ($normalized === '') {
            return 0;
        }

        return (int) ceil(Str::length($normalized) / 4);
    }

    private function removeNodes(DOMDocument $document): void
    {
        $tags = ['script', 'style', 'noscript', 'svg', 'canvas', 'iframe', 'form'];

        foreach ($tags as $tag) {
            while (($node = $document->getElementsByTagName($tag)->item(0)) instanceof DOMNode) {
                $node->parentNode?->removeChild($node);
            }
        }
    }

    private function renderChildren(DOMNode $node): string
    {
        $output = '';

        foreach ($node->childNodes as $child) {
            $output .= $this->renderNode($child);
        }

        return $output;
    }

    private function renderNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return $this->normalizeText($node->wholeText);
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        if ($this->shouldSkip($node)) {
            return '';
        }

        $content = $this->renderChildren($node);
        $tag = strtolower($node->tagName);

        return match ($tag) {
            'h1' => '# '.$this->inline($content)."\n\n",
            'h2' => '## '.$this->inline($content)."\n\n",
            'h3' => '### '.$this->inline($content)."\n\n",
            'h4' => '#### '.$this->inline($content)."\n\n",
            'h5' => '##### '.$this->inline($content)."\n\n",
            'h6' => '###### '.$this->inline($content)."\n\n",
            'p' => $this->inline($content)."\n\n",
            'br' => "  \n",
            'hr' => "---\n\n",
            'strong', 'b' => '**'.$this->inline($content).'**',
            'em', 'i' => '*'.$this->inline($content).'*',
            'code' => $node->parentNode instanceof DOMElement && strtolower($node->parentNode->tagName) === 'pre'
                ? $content
                : '`'.$this->inline($content).'`',
            'pre' => "```\n".$this->trimBlock($node->textContent)."\n```\n\n",
            'a' => $this->renderLink($node, $content),
            'img' => $this->renderImage($node),
            'ul' => $this->renderList($node, false),
            'ol' => $this->renderList($node, true),
            'blockquote' => $this->renderBlockquote($content),
            'article', 'section', 'main', 'div' => $this->block($content),
            'span', 'small', 'label' => $content,
            default => $this->block($content),
        };
    }

    private function renderLink(DOMElement $node, string $content): string
    {
        $href = trim((string) $node->getAttribute('href'));
        $label = $this->inline($content);

        if ($href === '') {
            return $label;
        }

        return '['.($label !== '' ? $label : $href).']('.$href.')';
    }

    private function renderImage(DOMElement $node): string
    {
        $src = trim((string) $node->getAttribute('src'));
        $alt = trim((string) $node->getAttribute('alt'));

        if ($src === '') {
            return '';
        }

        return '!['.$alt.']('.$src.')';
    }

    private function renderList(DOMElement $node, bool $ordered): string
    {
        $lines = [];
        $index = 1;

        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $prefix = $ordered ? $index.'. ' : '- ';
            $lines[] = $prefix.$this->inline($this->renderChildren($child));
            $index++;
        }

        if ($lines === []) {
            return '';
        }

        return implode("\n", $lines)."\n\n";
    }

    private function renderBlockquote(string $content): string
    {
        $lines = preg_split('/\R/u', $this->trimBlock($content)) ?: [];
        $lines = array_map(fn (string $line) => '> '.trim($line), array_filter($lines, fn (string $line) => trim($line) !== ''));

        return $lines === [] ? '' : implode("\n", $lines)."\n\n";
    }

    private function block(string $content): string
    {
        $content = $this->trimBlock($content);

        return $content === '' ? '' : $content."\n\n";
    }

    private function inline(string $content): string
    {
        $content = preg_replace('/[ \t]+/u', ' ', $content) ?? $content;
        $content = preg_replace("/\n{2,}/", "\n", $content) ?? $content;

        return trim($content);
    }

    private function trimBlock(string $content): string
    {
        $content = preg_replace('/[ \t]+\n/u', "\n", $content) ?? $content;
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;

        return trim($content);
    }

    private function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }

    private function shouldSkip(DOMElement $node): bool
    {
        $tag = strtolower($node->tagName);

        if (in_array($tag, ['nav', 'footer'], true)) {
            return true;
        }

        $attributes = strtolower(trim(
            $node->getAttribute('aria-hidden').' '.
            $node->getAttribute('hidden').' '.
            $node->getAttribute('class').' '.
            $node->getAttribute('id')
        ));

        return str_contains($attributes, 'sr-only');
    }

    private function escapeFrontmatter(string $value): string
    {
        return str_replace(["\r", "\n"], ' ', $value);
    }
}
