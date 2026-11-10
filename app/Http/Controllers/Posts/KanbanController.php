<?php

declare(strict_types=1);
namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\KanbanReadRequest;
use App\Http\Requests\Posts\KanbanCommandRequest;
use App\Services\Posts\Kanban;
use App\Support\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Throwable;

/** Present the full-screen content board through its authorized domain adapter. */
class KanbanController extends Controller
{
    /** Render the board and its safe Domi presentation registry.
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
        Page::prepare('posts-workflow');
        Page::addLocalize('kanban', $board->page($type, $request->user()));
        return view('posts.kanban', compact('type'));
    }

    /** Return independently paginated, role-filtered columns.
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
        $result = $board->read($type, $request->validated(), $request->user());
        return response()->json($result, $result->status);
    }

    /** Inspect one visible card and its current destinations without side effects.
     *
     * @param KanbanReadRequest $request
     * @param string $type
     * @param int $post
     * @param Kanban $board
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function details(KanbanReadRequest $request, string $type, int $post, Kanban $board): JsonResponse
    {
        $result = $board->details($type, $post, $request->user(), $request->validated('locale'));
        return response()->json($result, $result->status);
    }

    /** Execute a validated preference or movement command without navigation.
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
        $result = $board->command($type, $request->validated(), $request->user());
        return response()->json($result, $result->status);
    }
}
