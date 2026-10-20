<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\PostLifecycleRequest;
use App\Services\Posts\PostLifecycleView;
use App\Services\Taxonomies\TaxonomyEditLocks;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use JobMetric\Post\Exceptions\PostStatusTransitionNotAllowedException;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Services\PostStreamWorkflow;
use Throwable;

/** Apply reviewed content actions only to the locked owning post. */
final class PostLifecycleController extends Controller
{
    /**
     * Execute one version-checked, authorized lifecycle operation.
     *
     * @param PostLifecycleRequest $request
     * @param string $type
     * @param int $post
     * @param PostLifecycle $lifecycle
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function command(PostLifecycleRequest $request, string $type, int $post, PostLifecycle $lifecycle): JsonResponse
    {
        $record = Post::query()
            ->where('type', $type)
            ->findOrFail($post);

        $data = $request->validated();

        if (app(PostLifecycleView::class)->activeDefinition($type) && $data['operation'] !== 'transition') {
            throw ValidationException::withMessages(['operation' => [trans('posts.lifecycle.transition_required')]]);
        }

        if (PostTypeRegistry::for($type)
            ->hasOptimisticLocking()) {
            abort_unless(app(TaxonomyEditLocks::class)->canSave('posts:' . $type, $post, (int) $request->user()
                ->getAuthIdentifier(), (string) ($data['_edit_lock'] ?? '')), 423);
        }

        $locale = $data['locale'];
        $revision = (int) ($data['revision_id'] ?? 0);
        $version = (int) $data['version'];
        $actor = $request->user();
        try {
            $result = match ($data['operation']) {
                'transition' => app(PostStreamWorkflow::class)->transition($record, $locale, $data['transition'], $revision, $version, $actor, isset($data['due_at']) ? Carbon::parse($data['due_at']) : null),
                'submit' => $lifecycle->submit($record, $locale, $revision, $actor, $version),
                'approve' => $lifecycle->approve($record, $locale, $revision, $actor, $version),
                'reject' => $lifecycle->reject($record, $locale, $revision, $actor, $version),
                'publish' => $lifecycle->publish($record, $locale, $revision, $actor, $version),
                'schedule', 'resume' => $lifecycle->schedule($record, $locale, $revision, Carbon::parse($data['due_at']), $actor, $data['operation'] === 'resume' ? (int) $data['schedule_id'] : null, $version),
                'cancel_schedule' => $lifecycle->cancelSchedule($record, $locale, (int) $data['schedule_id'], $version, $actor),
                'withdraw' => $lifecycle->withdraw($record, $locale, $version, $actor),
            };
        } catch (PostStatusTransitionNotAllowedException) {
            throw ValidationException::withMessages(['transition' => [trans('posts.workflow_status')]]);
        }

        return response()->json([
            'ok'   => true,
            'data' => [
                'id'        => $result->id,
                'operation' => $data['operation'],
            ],
        ]);
    }
}
