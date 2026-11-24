<?php
declare(strict_types=1);

namespace App\Extensions\Module\JobicSlideshow;

use App\Support\GalleryItemForms;
use Illuminate\Validation\Rule;
use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;
use JobMetric\Media\Http\Resources\MediaResource;
use JobMetric\Media\Models\Media;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Services\PostLifecycle;

/** Register a bundled slideshow extension. */
final class JobicSlideshow extends AbstractExtension
{
    /**
     * Configure the bundled extension.
     *
     * @param ExtensionCore $extension
     *
     * @return void
     */
    public function configuration(ExtensionCore $extension): void {}

    /**
     * Provide the instance configuration form.
     *
     * @return FormBuilder
     */
    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('slideshow')->label('site.extensions.slideshow')
                ->customField(function (CustomFieldBuilder $field): void {
                    $options = [['label' => __('site.starter_slides'), 'value' => '']];
                    foreach (Post::query()->where('type', 'banner')->orderBy('id')->cursor() as $banner) {
                        $options[] = [
                            'label' => $banner->getTranslation('title') ?: '#'.$banner->id,
                            'value' => (string) $banner->id,
                        ];
                    }

                    $field::select()->name('banner_id')->label('posts.banners')->options($options)
                        ->validation(['nullable', 'integer', Rule::exists(config('post.tables.post', 'posts'), 'id')
                            ->where('type', 'banner')->whereNull('deleted_at')]);
                });
        });
    }

    /**
     * Render the starter slideshow content.
     *
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    public function handle(array $options = []): ?string
    {
        if (empty($options['banner_id'])) {
            return view('site.starter.slideshow')->render();
        }

        $banner = Post::query()->where('type', 'banner')->published()->find((int) $options['banner_id']);
        if (! $banner) {
            return null;
        }

        $snapshot = app(PostLifecycle::class)->published($banner, app()->getLocale());
        $shared = $snapshot['shared'] ?? [];
        $gallery = collect($shared['media'] ?? [])->where('collection', 'gallery')->pluck('id')->all();
        $metadata = $shared['metadata'][GalleryItemForms::METADATA_KEY]['gallery'] ?? [];
        $ids = array_values(array_unique([...array_intersect(array_keys($metadata), $gallery), ...$gallery]));
        $images = Media::query()->whereIn('id', $ids)->get()->keyBy('id');
        $slides = [];
        foreach ($ids as $id) {
            if (! $images->has($id)) {
                continue;
            }
            $image = MediaResource::make($images->get($id))->resolve(request());
            if (filled($image['src'] ?? null)) {
                $slides[] = [
                    'src' => $image['src'],
                    'title' => $metadata[$id]['title'] ?? '',
                    'description' => $metadata[$id]['description'] ?? '',
                ];
            }
        }

        return $slides === [] ? null : view('site.starter.banner-slideshow', compact('slides'))->render();
    }
}
