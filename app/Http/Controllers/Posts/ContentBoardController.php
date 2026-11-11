<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\ContentBoardBulkRequest;
use App\Http\Requests\Posts\ContentBoardReadRequest;
use App\Http\Requests\Posts\ContentBoardRequest;
use App\Services\Posts\ContentBoards;
use App\Support\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/** Adapt authorized board directory and management requests to domain responses. */
class ContentBoardController extends Controller
{
    /**
     * Render the create or edit form through the shared administration layout.
     *
     * @param ContentBoardReadRequest $request
     * @param string $type
     * @param ContentBoards $boards
     *
     * @return View
     * @throws \Throwable
     */
    public function form(ContentBoardReadRequest $request, string $type, ContentBoards $boards): View
    {
        $data = $boards->formPage($type, $request->route('board') ? (int) $request->route('board') : null, $request->user());
        Page::prepare('kanban-board-form');
        Page::addLocalize('languages', trans('languages'));
        Page::addLocalize('kanbanBoardForm', $data['config']);

        return view('posts.kanban-board-form', ['type' => $type, 'record' => $data['record'], 'choices' => $data['config']]);
    }

    /**
     * Read the accessible board directory.
     *
     * @param ContentBoardReadRequest $request
     * @param string $type
     * @param ContentBoards $boards
     *
     * @return JsonResponse
     */
    public function data(ContentBoardReadRequest $request, string $type, ContentBoards $boards): JsonResponse
    {
        $result = $boards->listing($type, $request->validated(), $request->user());

        return response()->json($result, $result->status);
    }

    /**
     * Read definitions only for users authorized to manage boards.
     *
     * @param ContentBoardReadRequest $request
     * @param string $type
     * @param ContentBoards $boards
     *
     * @return JsonResponse
     */
    public function manage(ContentBoardReadRequest $request, string $type, ContentBoards $boards): JsonResponse
    {
        $result = $boards->listing($type, $request->validated(), $request->user(), true);

        return response()->json($result, $result->status);
    }

    /**
     * Persist a new or existing board after request and domain validation.
     *
     * @param ContentBoardRequest $request
     * @param string $type
     * @param ContentBoards $boards
     *
     * @return JsonResponse
     */
    public function save(ContentBoardRequest $request, string $type, ContentBoards $boards): JsonResponse
    {
        $result = $boards->save($type, $request->route('board') ? (int) $request->route('board') : null, $request->validated(), $request->user());

        return response()->json($result, $result->status);
    }

    /**
     * Remove a version-matched custom board without deleting content.
     *
     * @param ContentBoardReadRequest $request
     * @param string $type
     * @param ContentBoards $boards
     *
     * @return JsonResponse
     */
    public function destroy(ContentBoardReadRequest $request, string $type, ContentBoards $boards): JsonResponse
    {
        $result = $boards->delete($type, (int) $request->route('board'), (int) $request->validated('version'), $request->user());

        return response()->json($result, $result->status);
    }

    /**
     * Return bounded setting history after management authorization.
     *
     * @param ContentBoardReadRequest $request
     * @param string $type
     * @param ContentBoards $boards
     *
     * @return JsonResponse
     */
    public function history(ContentBoardReadRequest $request, string $type, ContentBoards $boards): JsonResponse
    {
        $result = $boards->history($type, (int) $request->route('board'), (int) ($request->validated('page') ?? 1), $request->user());

        return response()->json($result, $result->status);
    }

    /**
     * Delete a bounded selection atomically using the shared response contract.
     *
     * @param ContentBoardBulkRequest $request
     * @param string $type
     * @param ContentBoards $boards
     *
     * @return JsonResponse
     */
    public function bulk(ContentBoardBulkRequest $request, string $type, ContentBoards $boards): JsonResponse
    {
        $result = $boards->bulk($type, $request->validated(), $request->user());

        return response()->json($result,$result->status);
    }
}
