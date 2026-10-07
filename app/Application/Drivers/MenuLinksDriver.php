<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\Hooks;
use App\MenuLinks\ExternalUrlLinkDriver;
use App\MenuLinks\NoneLinkDriver;
use App\MenuLinks\SeparatorLinkDriver;
use App\Support\MenuLinks\MenuLinkRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Validation\Rule;
use JobMetric\Taxonomy\Models\Taxonomy;

/** Register built-in menu destinations and inject their form through a taxonomy hook. */
final class MenuLinksDriver implements ApplicationDriver
{
    public function register(Application $app): void
    {
        $app->singleton(MenuLinkRegistry::class);
    }

    public function boot(Application $app): void
    {
        $registry = $app->make(MenuLinkRegistry::class);

        $registry->register(new NoneLinkDriver)
            ->register(new ExternalUrlLinkDriver)
            ->register(new SeparatorLinkDriver);

        Hooks::addFilter('jobic.taxonomies.validation.rules', function (array $rules, array $context) use ($registry): array {
            $type = (string) ($context['type'] ?? '');

            if (preg_match('/^menu_\d+$/', $type) !== 1) {
                return $rules;
            }

            $request = $context['request'] ?? null;
            $driverKey = (string) ($request?->input('menu_link_driver', 'none') ?? 'none');
            $rules['menu_link_driver'] = [
                'sometimes',
                'string',
                Rule::in(array_keys($registry->all())),
            ];

            if ($registry->has($driverKey)) {
                $rules = array_replace($rules, $registry->get($driverKey)
                    ->rules());
            }

            return $rules;
        });

        Hooks::addFilter('jobic.taxonomies.history.snapshot', function (array $state, array $context): array {
            if (preg_match('/^menu_\d+$/', (string) ($context['type'] ?? '')) !== 1) {
                return $state;
            }

            $record = $context['record'] ?? null;
            if (! $record instanceof Taxonomy) {
                return $state;
            }

            $driver = $record->metas()
                ->where('key', 'menu_link_driver')
                ->value('value');

            $linkData = $record->metas()
                ->where('key', 'menu_link_data')
                ->first();

            $state['link'] = $driver ? [
                'driver' => $driver,
                'data'   => $linkData?->is_json ? json_decode((string) $linkData->value, true) : [],
            ] : null;

            return $state;
        });

        Hooks::addFilter('jobic.taxonomies.driver.fields', function (string $html, array $context) use ($registry): string {
            if (preg_match('/^menu_\d+$/', (string) ($context['type'] ?? '')) !== 1) {
                return $html;
            }

            $driver = (string) ($context['driver'] ?? '');
            if (! $registry->has($driver)) {
                return $html;
            }

            return view('taxonomies.partials.menu-link-fields', [
                'registry' => $registry,
                'driver'   => $driver,
                'values'   => [],
            ])->render();
        });

        Hooks::addAction('jobic.taxonomies.persisted', function (array $context) use ($registry): void {
            $type = (string) ($context['type'] ?? '');
            $data = (array) ($context['data'] ?? []);
            $record = $context['record'] ?? null;

            if (preg_match('/^menu_\d+$/', $type) !== 1 || ! $record instanceof Taxonomy || ! array_key_exists('menu_link_driver', $data)) {
                return;
            }

            $driverKey = (string) $data['menu_link_driver'];
            $driver = $registry->get($driverKey);
            $linkData = collect(array_keys($driver->rules()))
                ->mapWithKeys(fn (string $key): array => [$key => $data[$key] ?? null])
                ->all();

            $record->metas()
                ->updateOrCreate(['key' => 'menu_link_driver'], [
                    'value'   => $driverKey,
                    'is_json' => false,
                ]);

            $record->metas()
                ->updateOrCreate(['key' => 'menu_link_data'], [
                    'value'   => json_encode($linkData, JSON_UNESCAPED_UNICODE),
                    'is_json' => true,
                ]);
        });

        Hooks::addAction('jobic.admin.taxonomies.form.main.after', function (array $context) use ($registry): string {
            if (preg_match('/^menu_\d+$/', (string) ($context['type'] ?? '')) !== 1) {
                return '';
            }

            $record = filled($context['id'] ?? null) ? Taxonomy::query()
                ->find($context['id']) : null;

            $driver = (string) ($record?->metas()
                ->where('key', 'menu_link_driver')
                ->value('value') ?: 'none');

            $raw = $record?->metas()
                ->where('key', 'menu_link_data')
                ->first();

            $values = $raw ? ($raw->is_json ? json_decode((string) $raw->value, true) : []) : [];

            return view('taxonomies.partials.menu-link', compact('registry', 'driver', 'values') + $context)->render();
        });
    }
}
