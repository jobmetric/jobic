<?php

declare(strict_types=1);

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Http\Requests\Media\MediaManagerRequest;
use App\Services\Media\MediaManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use JobMetric\Media\FileManager;
use JobMetric\Media\FilePreview;
use JobMetric\Media\Models\Media;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Route authorized file-manager commands to the package-backed domain adapter. */
class MediaManagerController extends Controller
{
    /** Return a bounded folder listing.
     *
     * @param MediaManagerRequest $request
     * @param MediaManager $manager
     *
     * @return JsonResponse
     */
    public function browse(MediaManagerRequest $request, MediaManager $manager): JsonResponse
    {
        return response()->json($manager->browse($request->validated()));
    }

    /** Dispatch an allowlisted mutation after validation and authorization.
     *
     * @param MediaManagerRequest $request
     * @param MediaManager $manager
     * @param Media|null $media
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function command(MediaManagerRequest $request, MediaManager $manager, ?Media $media = null): JsonResponse
    {
        $operation = (string) $request->route('operation');
        $result = $manager->execute($operation, $request->validated(), $media);

        return response()->json([
            'ok'      => $result->ok,
            'message' => $result->message,
            'data'    => $result->data,
            'code'    => $result->data['conflict_code'] ?? null,
            'errors'  => $result->errors,
        ], $result->status);
    }

    /** Return file usages for the details panel.
     *
     * @param Media $media
     * @param MediaManager $manager
     *
     * @return JsonResponse
     */
    public function usages(Media $media, MediaManager $manager): JsonResponse
    {
        return response()->json([
            'ok'   => true,
            'data' => $manager->usages($media),
        ]);
    }

    /** Return one media record's display details.
     *
     * @param Media $media
     * @param MediaManager $manager
     *
     * @return JsonResponse
     */
    public function details(Media $media, MediaManager $manager): JsonResponse
    {
        return response()->json([
            'ok'      => true,
            'data'    => $manager->transform($media),
            'used_in' => $manager->usages($media),
        ]);
    }

    /** Return bounded spreadsheet or archive preview data.
     *
     * @param MediaManagerRequest $request
     * @param Media $media
     *
     * @return JsonResponse
     */
    public function preview(MediaManagerRequest $request, Media $media): JsonResponse
    {
        return response()->json([
            'ok'   => true,
            'data' => app(FilePreview::class)->preview($media, $request->validated()),
        ]);
    }

    /** Serve authorized media with range support for audio, video and PDF previews.
     *
     * @param Media $media
     *
     * @return Response
     */
    public function content(Media $media): Response
    {
        abort_unless($media->type === 'f', 404);

        $disk = Storage::disk($media->disk);
        $path = app(FileManager::class)->path($media);

        abort_unless($disk->exists($path), 404);

        if (config('filesystems.disks.' . $media->disk . '.driver') === 'local') {
            return response()->file($disk->path($path), [
                'Content-Type'           => $media->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control'          => 'private, max-age=3600',
            ]);
        }

        return $disk->response($path, $media->name . '.' . $media->extension, [
            'Content-Type'           => $media->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
