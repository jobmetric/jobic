<?php

declare(strict_types=1);

namespace App\Http\Controllers\Languages;

use App\Http\Controllers\Controller;
use App\Http\Requests\Languages\BulkRequest;
use App\Http\Requests\Languages\HistoryRequest;
use App\Http\Requests\Languages\LanguageRequest;
use App\Http\Requests\Languages\TableRequest;
use App\Services\Languages\Languages;
use App\Support\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use Throwable;

/**
 * Present language pages and map validated CMS requests to service responses.
 */
class LanguageController extends Controller
{
    /**
     * Resolve the language aggregate service.
     *
     * @param Languages $languages
     *
     * @return void
     */
    public function __construct(private readonly Languages $languages)
    {
    }

    /**
     * Render an empty table shell and register its page-only assets.
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function index(): View
    {
        $this->prepare();
        Domi::setPlugins(['jquery', 'datatable']);

        return view('languages.index');
    }

    /**
     * Return the requested server-side table page.
     *
     * @param TableRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function data(TableRequest $request): JsonResponse
    {
        return response()->json($this->languages->table($request->validated()));
    }

    /**
     * Render a blank language form.
     *
     * @return View
     */
    public function create(): View
    {
        $this->prepare();

        return view('languages.form', [
            'record'   => null,
            'readonly' => false,
        ]);
    }

    /**
     * Render an existing language for editing.
     *
     * @param int $language
     *
     * @return View
     * @throws ModelNotFoundException
     */
    public function edit(int $language): View
    {
        $this->prepare();

        return view('languages.form', [
            'record'   => $this->languages->record($language),
            'readonly' => false,
        ]);
    }

    /**
     * Render a language without editable controls.
     *
     * @param int $language
     *
     * @return View
     * @throws ModelNotFoundException
     */
    public function show(int $language): View
    {
        $this->prepare();

        return view('languages.form', [
            'record'   => $this->languages->record($language),
            'readonly' => true,
        ]);
    }

    /**
     * Create a language from validated form values.
     *
     * @param LanguageRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(LanguageRequest $request): JsonResponse
    {
        $result = $this->languages->saveRecord($request->validated(), null, (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Persist edits to an existing language.
     *
     * @param LanguageRequest $request
     * @param int $language
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(LanguageRequest $request, int $language): JsonResponse
    {
        $result = $this->languages->saveRecord($request->validated(), $language, (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Apply a validated selection operation.
     *
     * @param BulkRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function bulk(BulkRequest $request): JsonResponse
    {
        $result = $this->languages->bulk($request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Return validated history pagination for an authenticated reader.
     *
     * @param HistoryRequest $request
     * @param int $language
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function history(HistoryRequest $request, int $language): JsonResponse
    {
        return response()->json($this->languages->history($language, $request->validated()));
    }

    /**
     * Publish translated labels, routes and toolbar keys through Domi.
     *
     * @return void
     */
    private function prepare(): void
    {
        Page::prepare('languages');
        Page::addLocalize('languages', trans('languages'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        Page::addLocalize('languageRoutes', [
            'index' => route('admin.settings.languages.index'),
            'data'  => route('admin.settings.languages.data'),
            'bulk'  => route('admin.settings.languages.bulk'),
        ]);
        Page::addLocalize('languageToolbar', [
            'list' => [
                'activate',
                'deactivate',
                'delete',
                'help',
            ],
            'form' => [
                'new',
                'close',
            ],
        ]);
    }
}
