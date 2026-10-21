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

    /**
     * Register an action callback at the given priority.
     *
     * @param string $name
     * @param callable $callback
     * @param int $priority
     *
     * @return void
     */
    public function addAction(string $name, callable $callback, int $priority = 10): void
    {
        $this->listeners[$name][$priority][] = $callback;
    }

    /**
     * Register a value filter at the given priority.
     *
     * @param string $name
     * @param callable $callback
     * @param int $priority
     *
     * @return void
     */
    public function addFilter(string $name, callable $callback, int $priority = 10): void
    {
        $this->addAction($name, $callback, $priority);
    }

    /**
     * Invoke action callbacks in priority order.
     *
     * @param string $name
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function action(string $name, array $context = []): void
    {
        foreach ($this->callbacks($name) as $callback) {
            $callback($context);
        }
    }

    /**
     * Dispatch a synchronous domain boundary followed by its legacy callbacks.
     *
     * @param string $name
     * @param array<string, mixed> $context
     *
     * @return void
     * @throws \Throwable When a domain listener or compatibility callback vetoes the operation.
     */
    public function lifecycle(string $name, array $context = []): void
    {
        event(LifecycleEvents::make($name, $context));
        $this->action($name, $context);
    }

    /**
     * Post-save observers cannot turn a committed save into an apparent failure.
     *
     * @param string $name
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function notify(string $name, array $context = []): void
    {
        foreach ($this->callbacks($name) as $callback) {
            try {
                $callback($context);
            } catch (Throwable $exception) {
                Log::error('CMS hook observer failed.', [
                    'hook'           => $name,
                    'exception_type' => $exception::class,
                ]);
            }
        }
    }

    /**
     * Pass a value through the registered filters.
     *
     * @param string $name
     * @param mixed $value
     * @param array<string, mixed> $context
     *
     * @return mixed
     */
    public function filter(string $name, mixed $value, array $context = []): mixed
    {
        foreach ($this->callbacks($name) as $callback) {
            $value = $callback($value, $context);
        }

        return $value;
    }

    /**
     * Register a Blade view for an extension slot.
     *
     * @param string $name
     * @param string $view
     * @param int $priority
     *
     * @return void
     */
    public function addView(string $name, string $view, int $priority = 10): void
    {
        $this->addAction($name, fn (array $context) => view($view, $context)->render(), $priority);
    }

    /**
     * Render the registered views for an extension slot.
     *
     * @param string $name
     * @param array<string, mixed> $context
     *
     * @return HtmlString
     */
    public function render(string $name, array $context = []): HtmlString
    {
        $html = '';
        foreach ($this->callbacks($name) as $callback) {
            $html .= $callback($context);
        }

        return new HtmlString($html);
    }

    /**
     * Get callbacks ordered by priority and registration.
     *
     * @param string $name
     *
     * @return list<callable>
     */
    private function callbacks(string $name): array
    {
        $groups = $this->listeners[$name] ?? [];
        ksort($groups, SORT_NUMERIC);

        return array_merge([], ...array_values($groups));
    }
}
