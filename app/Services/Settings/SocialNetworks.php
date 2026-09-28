<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Support\SocialNetworks\SocialNetworkRegistry;
use JobMetric\Setting\Facades\Setting;

/**
 * Expose configured social profiles with their canonical icons and URLs.
 */
readonly class SocialNetworks
{
    /**
     * Create the social-network reader with its shared registry.
     *
     * @param SocialNetworkRegistry $registry
     *
     * @return void
     */
    public function __construct(private SocialNetworkRegistry $registry)
    {
    }

    /**
     * Return valid configured social profiles for website presentation.
     *
     * @return list<array{type: string, name: string, icon: string, slug: string, url: string}>
     */
    public function configured(): array
    {
        $items = Setting::get('jobic_social_networks_networks', []);

        if (is_string($items)) {
            $items = json_decode($items, true);
        }

        $resolved = [];
        foreach (is_array($items) ? $items : [] as $item) {
            $type = is_array($item) ? (string) ($item['type'] ?? '') : '';
            $slug = is_array($item) ? trim((string) ($item['slug'] ?? '')) : '';
            $network = $this->registry->get($type);
            $url = $this->registry->url($type, $slug);
            if ($network === null || $slug === '' || $url === null) {
                continue;
            }

            $resolved[] = [
                'type' => $type,
                'name' => trans($network['name']),
                'icon' => $network['icon'],
                'slug' => $slug,
                'url'  => $url,
            ];
        }

        return $resolved;
    }
}
