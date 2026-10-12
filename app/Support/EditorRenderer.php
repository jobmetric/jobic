<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/** Render registered block documents with safe inline HTML and driver-owned views. */
final class EditorRenderer
{
    /**
     * Render a document using registered block views.
     *
     * @param array<string, mixed>|string|null $document
     * @param int $depth
     * @param bool $preview
     *
     * @return string
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function render(array|string|null $document, int $depth = 0, bool $preview = false): string
    {
        if ($depth > 8) {
            return '';
        }

        $document = is_string($document) ? json_decode($document, true) : $document;
        $html = '';
        $registry = app(EditorDriverRegistry::class);
        foreach (array_slice((array) ($document['blocks'] ?? []), 0, 1000) as $block) {
            if (! is_array($block) || ! $registry->has($block['type'] ?? '')) {
                continue;
            }

            $driver = $registry->get($block['type']);
            $data = $block['data'] ?? [];
            if (! $driver->validate($data)) {
                continue;
            }

            $view = $driver->presentation['view'] ?? null;
            if ($preview) {
                foreach ($driver->presentation['mediaPaths'] ?? [] as $path) {
                    $multiple = str_ends_with($path, '.*');
                    $files = data_get($data, $path, []);
                    $files = $multiple ? $files : [$files];

                    foreach ($files as &$file) {
                        if (! empty($file['id'])) {
                            $file['url'] = route('admin.media-manager.content', ['media' => $file['id']]);
                        }
                    }

                    unset($file);

                    data_set($data, $multiple ? substr($path, 0, -2) : $path, $multiple ? $files : ($files[0] ?? []));
                }
            }

            if ($view) {
                $html .= view($view, [
                    'data' => $data,
                    'renderer' => $this,
                    'depth' => $depth,
                    'preview' => $preview,
                ])->render();
            }
        }

        return $html;
    }

    /**
     * Allow formatting tags and safe links while removing executable markup.
     *
     * @param string $html
     *
     * @return string
     */
    public function inline(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $root = $document->getElementsByTagName('div')
            ->item(0);
        if (! $root) {
            return '';
        }

        return $this->cleanChildren($root);
    }

    /**
     * Serialize allowed inline elements without untrusted attributes.
     *
     * @param DOMNode $node
     *
     * @return string
     */
    private function cleanChildren(DOMNode $node): string
    {
        $output = '';
        foreach ($node->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                $output .= $child->nodeType === XML_TEXT_NODE ? e($child->textContent) : '';
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, [
                'script',
                'style',
                'iframe',
                'object',
                'svg',
                'math',
            ], true)) {
                continue;
            }
            $content = $this->cleanChildren($child);
            if (! in_array($tag, [
                'b',
                'strong',
                'i',
                'em',
                'u',
                's',
                'del',
                'mark',
                'code',
                'br',
                'a',
                'sup',
                'sub',
            ], true)) {
                $output .= $content;
                continue;
            }
            $attributes = '';
            if ($tag === 'a') {
                $url = $this->url($child->getAttribute('href'));
                $attributes = $url !== '' ? ' href="' . e($url) . '" rel="noopener noreferrer"' : '';
            }
            $output .= $tag === 'br' ? '<br>' : '<' . $tag . $attributes . '>' . $content . '</' . $tag . '>';
        }

        return $output;
    }

    /**
     * Accept web addresses and local paths for rendered links and media.
     *
     * @param string $url
     *
     * @return string
     */
    public function url(string $url): string
    {
        if (preg_match('/[\x00-\x20]/', $url)) {
            return '';
        }

        return preg_match('~^https?://~i', $url) || (str_starts_with($url, '/') && ! str_starts_with($url, '//')) || str_starts_with($url, '#') ? $url : '';
    }
}
