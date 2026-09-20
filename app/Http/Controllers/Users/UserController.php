<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\BulkRequest;
use App\Http\Requests\Users\HistoryRequest;
use App\Http\Requests\Users\TableRequest;
use App\Http\Requests\Users\UserRequest;
use App\Services\Users\Users;
use App\Support\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use JobMetric\PackageCore\Output\Response;
use Throwable;

/**
 * Present user pages and map validated administration requests to the user service.
 */
class UserController extends Controller
{
    /**
     * Resolve the user aggregate service.
     *
     * @param Users $users
     *
     * @return void
     */
    public function __construct(private readonly Users $users)
    {
    }

    /**
     * Render the empty server-side user table shell.
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function index(): View
    {
        $this->prepare();
        Domi::setPlugins(['jquery', 'datatable']);

        return view('users.index');
    }

    /**
     * Return a validated server-side table page.
     *
     * @param TableRequest $request
     *
     * @return JsonResponse
     */
    public function data(TableRequest $request): JsonResponse
    {
        return response()->json($this->users->table($request->validated()));
    }

    /**
     * Render a blank user form.
     *
     * @return View
     */
    public function create(): View
    {
        $this->prepare();

        return view('users.form', ['record' => null, 'readonly' => false]);
    }

    /**
     * Render an editable existing user.
     *
     * @param int $user
     *
     * @return View
     * @throws ModelNotFoundException
     */
    public function edit(int $user): View
    {
        $this->prepare();

        return view('users.form', ['record' => $this->users->record($user), 'readonly' => false]);
    }

    /**
     * Render a read-only existing user.
     *
     * @param int $user
     *
     * @return View
     * @throws ModelNotFoundException
     */
    public function show(int $user): View
    {
        $this->prepare();

        return view('users.form', ['record' => $this->users->record($user), 'readonly' => true]);
    }

    /**
     * Create a user from validated form values.
     *
     * @param UserRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(UserRequest $request): JsonResponse
    {
        return $this->mutation($this->users->saveRecord($request->validated(), null, (int) $request->user()->id));
    }

    /**
     * Persist edits to an existing user.
     *
     * @param UserRequest $request
     * @param int $user
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(UserRequest $request, int $user): JsonResponse
    {
        return $this->mutation($this->users->saveRecord($request->validated(), $user, (int) $request->user()->id));
    }

    /**
     * Apply a validated user selection operation.
     *
     * @param BulkRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function bulk(BulkRequest $request): JsonResponse
    {
        return $this->mutation($this->users->bulk($request->validated(), (int) $request->user()->id));
    }

    /**
     * Return validated history pagination for an authenticated reader.
     *
     * @param HistoryRequest $request
     * @param int $user
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function history(HistoryRequest $request, int $user): JsonResponse
    {
        return response()->json($this->users->history($user, $request->validated()));
    }

    /**
     * Add mutation status and the renewed CSRF token to a JSON response.
     *
     * @param Response $result
     *
     * @return JsonResponse
     */
    private function mutation(Response $result): JsonResponse
    {
        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Publish translated labels, routes and toolbar keys through Domi.
     *
     * @return void
     * @throws SetPluginNotFoundException
     */
    private function prepare(): void
    {
        Page::prepare('users');
        Page::addLocalize('users', trans('users'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        Page::addLocalize('userRoutes', [
            'index' => route('admin.users.index'),
            'data'  => route('admin.users.data'),
            'bulk'  => route('admin.users.bulk'),
        ]);
        Page::addLocalize('userToolbar', [
            'list' => ['activate', 'deactivate', 'delete', 'help'],
            'form' => ['new', 'close'],
        ]);
    }
}
