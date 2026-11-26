<?php
declare(strict_types=1);

namespace App\Extensions\Module\JobicSlideshow;

use App\Support\GalleryItemForms;
use App\Support\LocaleCatalog;
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
            foreach (['carousel_loop' => '0', 'carousel_arrows' => '1', 'carousel_dots' => '0', 'carousel_captions' => '1'] as $name => $default) {
                $tab->customField(function (CustomFieldBuilder $field) use ($name, $default): void {
                    $field::select()->name($name)->label('site.'.$name)->value($default)->options([
                        ['label' => __('site.carousel_on'), 'value' => '1'],
                        ['label' => __('site.carousel_off'), 'value' => '0'],
                    ])->validation('nullable|in:0,1');
                });
            }
            $tab->customField(function (CustomFieldBuilder $field): void {
                $options = [['label' => __('site.carousel_off'), 'value' => '0']];
                foreach ([3, 5, 8, 12] as $seconds) {
                    $options[] = ['label' => __('site.carousel_seconds', ['count' => $seconds]), 'value' => (string) $seconds];
                }
                $field::select()->name('carousel_autoplay')->label('site.carousel_autoplay')->value('0')
                    ->options($options)->validation('nullable|in:0,3,5,8,12');
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
        $settings = [
            'loop' => (string) ($options['carousel_loop'] ?? '0') === '1',
            'arrows' => (string) ($options['carousel_arrows'] ?? '1') === '1',
            'dots' => (string) ($options['carousel_dots'] ?? '0') === '1',
            'captions' => (string) ($options['carousel_captions'] ?? '1') === '1',
            'interval' => (int) ($options['carousel_autoplay'] ?? 0),
        ];
        if (empty($options['banner_id'])) {
            return view('site.starter.slideshow', compact('settings'))->render();
        }

        $banner = Post::query()->where('type', 'banner')->published()->find((int) $options['banner_id']);
        if (! $banner) {
            return null;
        }

        $lifecycle = app(PostLifecycle::class);
        $snapshot = $lifecycle->published($banner, app()->getLocale());
        foreach (array_keys(LocaleCatalog::all()) as $locale) {
            if ($snapshot !== null) {
                break;
            }
            $snapshot = $lifecycle->published($banner, $locale);
        }
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
            if (($image['mime_group'] ?? null) === 'image') {
                $slides[] = [
                    'src' => route('media.image.responsive', ['uuid' => $image['uuid'], 'w' => 1440, 'h' => 960]),
                    'small_src' => route('media.image.responsive', ['uuid' => $image['uuid'], 'w' => 720, 'h' => 480]),
                    'title' => $metadata[$id]['title'] ?? '',
                    'description' => $metadata[$id]['description'] ?? '',
                ];
            }
        }

        return $slides === [] ? null : view('site.starter.banner-slideshow', compact('slides', 'settings'))->render();
    }
}
