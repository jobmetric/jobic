<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Models\User;
use App\Services\Posts\PostLifecycleView;
use App\Services\Posts\Posts;
use Illuminate\Database\Seeder;
use JobMetric\Post\Models\Post;
use RuntimeException;

/** Create one bilingual article and page through the normal editor/publication service. */
final class DemoPostsSeeder extends Seeder
{
    public const MARKER = 'jobic_demo_key';

    public function run(): void
    {
        $actor = User::query()->where('email', 'admin@jobic.test')->firstOrFail();
        $image = DemoMediaSeeder::image();
        $category = DemoTaxonomiesSeeder::find('category', 'getting-started');
        $tag = DemoTaxonomiesSeeder::find('tag', 'cms');

        $this->ensure('post', 'welcome-post', 'jobic-demo-welcome', [
            'fa' => [
                'title' => 'به Jobic خوش آمدید',
                'excerpt' => 'یک نوشتهٔ نمونه برای آشنایی با ویرایشگر، دسته‌بندی و انتشار محتوا.',
                'content' => $this->document('آشنایی با Jobic', [
                    'این نوشته یک محتوای نمونه است تا بتوانید ویرایشگر و گردش انتشار را امتحان کنید.',
                    'متن، تصویر و دسته‌بندی این نوشته را تغییر دهید یا پس از آماده‌سازی سایت آن را حذف کنید.',
                ], 'fa'),
            ],
            'en' => [
                'title' => 'Welcome to Jobic',
                'excerpt' => 'A sample article for trying the editor, categories, and publishing.',
                'content' => $this->document('Getting started with Jobic', [
                    'This sample article lets you explore the editor and publication workflow.',
                    'Edit its text, media, and categories, or remove it before launching your website.',
                ], 'en'),
            ],
        ], $actor, array_filter([
            'media' => $image ? ['base' => [(int) $image->id]] : null,
            'taxonomies' => ['categories' => $category ? [(int) $category->id] : [],
                'tags' => $tag ? [(int) $tag->id] : []],
        ]));

        $this->ensure('page', 'about-page', 'jobic-demo-about', [
            'fa' => [
                'title' => 'دربارهٔ این وب‌سایت',
                'excerpt' => 'برگهٔ نمونه برای معرفی وب‌سایت.',
                'content' => $this->document('دربارهٔ ما', [
                    'این برگه فقط یک نمونه است. داستان و اطلاعات واقعی مجموعهٔ خود را جایگزین آن کنید.',
                    'پیش از انتشار نهایی، راه‌های تماس و پیوندهای منو را بررسی کنید.',
                ], 'fa'),
            ],
            'en' => [
                'title' => 'About this website',
                'excerpt' => 'A sample page for introducing your website.',
                'content' => $this->document('About us', [
                    'This page is a sample. Replace it with the real story of your organization.',
                    'Check your contact details and navigation links before launch.',
                ], 'en'),
            ],
        ], $actor);
    }

    private function ensure(string $type, string $key, string $slug, array $translation,
        User $actor, array $relations = []): void
    {
        if (Post::query()->where('type', $type)
            ->whereHas('metas', fn ($query) => $query->where('key', self::MARKER)->where('value', $key))
            ->exists()) {
            return;
        }

        $advanced = app(PostLifecycleView::class)->activeDefinition($type) !== null;
        $result = app(Posts::class)->save($type, [
            'status' => $advanced ? 'editable' : 'publish',
            'slug' => $slug,
            'comment_status' => false,
            'translation' => $translation,
            'metadata' => [self::MARKER => $key],
            ...$relations,
        ], null, $actor);

        if (! $result->ok) {
            throw new RuntimeException('Cannot seed demo '.$type.' ['.$key.']: '.$result->message);
        }
    }

    private function document(string $heading, array $paragraphs, string $locale): array
    {
        return ['blocks' => [
            ['id' => 'demo-'.$locale.'-heading', 'type' => 'header',
                'data' => ['text' => $heading, 'level' => 2]],
            ...array_map(static fn (string $text, int $index): array => [
                'id' => 'demo-'.$locale.'-paragraph-'.$index,
                'type' => 'paragraph',
                'data' => ['text' => $text],
            ], $paragraphs, array_keys($paragraphs)),
        ]];
    }
}
