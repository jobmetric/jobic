<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\LocaleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Services\PostLifecycle;

/** Expose only live, language-scoped CMS revisions to public clients. */
final class PublishedContentController extends Controller
{
    /**
     * Return a published snapshot without falling back to another language.
     *
     * @param Request $request
     * @param string $type
     * @param int $post
     * @param PostLifecycle $lifecycle
     *
     * @return JsonResponse
     */
    public function show(Request $request, string $type, int $post, PostLifecycle $lifecycle): JsonResponse
    {
        abort_unless(PostTypeRegistry::has($type), 404);

        $locale = (string) $request->query('locale', app()->getLocale());

        abort_unless(in_array($locale, LocaleCatalog::codes(), true), 404);

        $record = Post::query()
            ->where('type', $type)
            ->findOrFail($post);

        abort_if($record->password !== null && $record->password !== '', 404);

        $snapshot = $lifecycle->published($record, $locale);

        abort_if($snapshot === null, 404);

        return response()
            ->json([
                'data' => $snapshot,
            ])
            ->header('Cache-Control', 'public, max-age=60');
    }
}
