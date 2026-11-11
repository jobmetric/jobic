<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\KanbanCommandRequest;
use App\Http\Requests\Posts\KanbanReadRequest;
use App\Services\Posts\ContentBoards;
use App\Services\Posts\Kanban;
use App\Support\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use JobMetric\Domi\Facades\Domi;
use JobMetric\Post\Facades\PostTypeRegistry;
use Throwable;

/** Present the full-screen content board through its authorized domain adapter. */
class KanbanController extends Controller
{
    /**
     * Render the board and its safe Domi presentation registry.
     *
     * @param KanbanReadRequest $request
     * @param string $type
     * @param Kanban $board
     *
     * @return View
     * @throws Throwable
     */
    public function index(KanbanReadRequest $request, string $type, Kanban $board): View
    {
        Page::prepare($request->routeIs('admin.posts.kanban.index') && PostTypeRegistry::for($type)->hasMultipleKanban() ? 'kanban-boards' : 'posts-workflow');
        if ($request->routeIs('admin.posts.kanban.index') && PostTypeRegistry::for($type)->hasMultipleKanban()) {
            Domi::setPlugins(['jquery', 'select2', 'datatable']);
            Page::addLocalize('languages', trans('languages'));
            $presentation = app(ContentBoards::class)->page($type, $request->user());
            Page::addLocalize('kanbanBoards', $presentation);

            return view('posts.kanban-boards', ['type'=>$type,'filterOptions'=>$presentation]);
        }
        $board->useBoard($type, $request->route('board') ? (int) $request->route('board') : null, $request->user());
        Page::addLocalize('kanban', $board->page($type, $request->user()));

        return view('posts.kanban', compact('type'));
    }

    /**
     * Return independently paginated, role-filtered columns.
     *
     * @param KanbanReadRequest $request
     * @param string $type
     * @param Kanban $board
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function data(KanbanReadRequest $request, string $type, Kanban $board): JsonResponse
    {
        $board->useBoard($type, $request->route('board') ? (int) $request->route('board') : null, $request->user());
        $result = $board->read($type, $request->validated(), $request->user());

        return response()->json($result, $result->status);
    }

    /**
     * Inspect one visible card and its current destinations without side effects.
     *
     * @param KanbanReadRequest $request
     * @param string $type
     * @param Kanban $board
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function details(KanbanReadRequest $request, string $type, Kanban $board): JsonResponse
    {
        $board->useBoard($type, $request->route('board') ? (int) $request->route('board') : null, $request->user());
        $board->checkVersion($request->validated());
        $result = $board->details($type, (int) $request->route('post'), $request->user(), $request->validated('locale'));

        return response()->json($result, $result->status);
    }

    /**
     * Execute a validated preference or movement command without navigation.
     *
     * @param KanbanCommandRequest $request
     * @param string $type
     * @param Kanban $board
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function command(KanbanCommandRequest $request, string $type, Kanban $board): JsonResponse
    {
        $board->useBoard($type, $request->route('board') ? (int) $request->route('board') : null, $request->user());
        $result = $board->command($type, $request->validated(), $request->user());

        return response()->json($result, $result->status);
    }
}
