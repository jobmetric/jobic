<?php

declare(strict_types=1);

namespace App\Support;

use App\Rules\EditorContentRule;
use Closure;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/** Store developer-defined editor documents for insertion into content fields. */
final class EditorTemplateRegistry
{
    private array $templates = [];

    /**
     * Register a translated template for the requested post types.
     *
     * @param string $key
     * @param string $label
     * @param array<string, mixed>|Closure $document
     * @param list<string> $types
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function register(string $key, string $label, array|Closure $document, array $types = []): self
    {
        if (! preg_match('/^[a-z][a-z0-9_-]*$/', $key) || isset($this->templates[$key])) {
            throw new InvalidArgumentException('Invalid or duplicate editor template: ' . $key);
        }
        $this->templates[$key] = compact('key', 'label', 'document', 'types');

        return $this;
    }

    /**
     * Return validated templates available to one post type.
     *
     * @param string $type
     *
     * @return list<array<string, mixed>>
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function forType(string $type): array
    {
        $result = [];
        foreach ($this->templates as $template) {
            if ($template['types'] !== [] && ! in_array($type, $template['types'], true)) {
                continue;
            }

            if ($template['document'] instanceof Closure) {
                $template['document'] = ($template['document'])();
            }

            (new EditorContentRule(app(EditorDriverRegistry::class)))->validate('content', $template['document'], static function (string $message): void {
                throw new InvalidArgumentException($message);
            });

            $template['label'] = __($template['label']);
            $template['preview'] = app(EditorRenderer::class)->render($template['document'], 0, true);

            $result[] = $template;
        }

        return $result;
    }
}
