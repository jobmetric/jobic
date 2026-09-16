<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Throwable;

/** Trusted, in-process extension points registered by service providers. */
class Hooks
{
    private array $listeners = [];

    public function addAction(string $name, callable $callback, int $priority = 10): void
    {
        $this->listeners[$name][$priority][] = $callback;
    }

    public function addFilter(string $name, callable $callback, int $priority = 10): void
    {
        $this->addAction($name, $callback, $priority);
    }

    public function action(string $name, array $context = []): void
    {
        foreach ($this->callbacks($name) as $callback) {
            $callback($context);
        }
    }

    /** Post-save observers cannot turn a committed save into an apparent failure. */
    public function notify(string $name, array $context = []): void
    {
        foreach ($this->callbacks($name) as $callback) {
            try {
                $callback($context);
            } catch (Throwable $exception) {
                Log::error('CMS hook observer failed.', ['hook' => $name, 'exception_type' => $exception::class]);
            }
        }
    }

    public function filter(string $name, mixed $value, array $context = []): mixed
    {
        foreach ($this->callbacks($name) as $callback) {
            $value = $callback($value, $context);
        }

        return $value;
    }

    public function addView(string $name, string $view, int $priority = 10): void
    {
        $this->addAction($name, fn (array $context) => view($view, $context)->render(), $priority);
    }

    public function render(string $name, array $context = []): HtmlString
    {
        $html = '';
        foreach ($this->callbacks($name) as $callback) {
            $html .= $callback($context);
        }

        return new HtmlString($html);
    }

    private function callbacks(string $name): array
    {
        $groups = $this->listeners[$name] ?? [];
        ksort($groups, SORT_NUMERIC);

        return array_merge([], ...array_values($groups));
    }
}
