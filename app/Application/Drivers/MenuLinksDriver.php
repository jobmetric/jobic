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
        $registry->register(new NoneLinkDriver)->register(new ExternalUrlLinkDriver)->register(new SeparatorLinkDriver);

        Hooks::addAction('jobic.admin.taxonomies.form.fields.after', function (array $context) use ($registry): string {
            if (preg_match('/^menu_\d+$/', (string) ($context['type'] ?? '')) !== 1) {
                return '';
            }

            $record = filled($context['id'] ?? null)
                ? Taxonomy::query()->find($context['id'])
                : null;
            $driver = (string) ($record?->metas()->where('key', 'menu_link_driver')->value('value') ?: 'none');
            $raw = $record?->metas()->where('key', 'menu_link_data')->first();
            $values = $raw ? ($raw->is_json ? json_decode((string) $raw->value, true) : []) : [];

            return view('taxonomies.partials.menu-link', compact('registry', 'driver', 'values') + $context)->render();
        });
    }
}
