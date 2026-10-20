<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JobMetric\Taxonomy\Facades\Taxonomy as TaxonomyService;
use JobMetric\Taxonomy\Models\Taxonomy;
use RuntimeException;

/** Create bilingual categories and tags through the registered taxonomy package. */
final class DemoTaxonomiesSeeder extends Seeder
{
    public const MARKER = 'jobic_demo_key';

    public function run(): void
    {
        $guides = $this->ensure('category', 'guides', 'راهنماهای نمونه', 'Sample guides', 'jobic-demo-guides');
        $this->ensure('category', 'getting-started', 'شروع کار', 'Getting started',
            'jobic-demo-getting-started', (int) $guides->id);
        $this->ensure('tag', 'cms', 'سیستم مدیریت محتوا', 'Content management', 'jobic-demo-cms');
        $this->ensure('tag', 'editor', 'ویرایشگر', 'Editor', 'jobic-demo-editor');
    }

    public static function find(string $type, string $key): ?Taxonomy
    {
        return Taxonomy::query()->where('type', $type)
            ->whereHas('metas', fn ($query) => $query->where('key', self::MARKER)->where('value', $key))
            ->first();
    }

    public function ensure(string $type, string $key, string $fa, string $en,
        ?string $slug = null, ?int $parentId = null): Taxonomy
    {
        return DB::transaction(function () use ($type, $key, $fa, $en, $slug, $parentId): Taxonomy {
            if ($existing = self::find($type, $key)) {
                return $existing;
            }

            $payload = [
                'type' => $type,
                'ordering' => 0,
                'status' => true,
                'translation' => [
                    'fa' => ['name' => $fa],
                    'en' => ['name' => $en],
                ],
            ];
            if ($slug !== null) {
                $payload['slug'] = $slug;
            }
            if ($parentId !== null) {
                $payload['parent_id'] = $parentId;
            }

            $result = TaxonomyService::store($payload);
            if (! ($result['ok'] ?? false)) {
                throw new RuntimeException('Cannot seed taxonomy ['.$type.':'.$key.']: '.json_encode($result['errors'] ?? []));
            }

            $taxonomy = Taxonomy::query()->findOrFail((int) $result['data']->id);
            $taxonomy->storeMetadata(self::MARKER, $key);

            return $taxonomy;
        });
    }
}
