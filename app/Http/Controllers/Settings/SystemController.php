<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\CacheRequest;
use App\Services\Settings\SystemSettings;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use Throwable;

/** Present technical settings within the existing Vuero administration shell. */
class SystemController extends Controller
{
    /**
     * Receive the system configuration service.
     *
     * @param SystemSettings $settings
     *
     * @return void
     */
    public function __construct(private readonly SystemSettings $settings)
    {
    }

    /**
     * Render read-only diagnostic results from the current server.
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function health(): View
    {
        Page::prepare('settings');
        Page::addLocalize('system', [
            'not_verified' => trans('system.not_verified'),
        ]);

        return view('settings.health', $this->settings->health());
    }

    /**
     * Render supported optional cache configuration.
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function edit(): View
    {
        Page::prepare('settings');

        return view('settings.cache', ['fields' => $this->settings->fields(), 'values' => $this->settings->values()]);
    }

    /**
     * Save or test validated cache values without changing the current session driver.
     *
     * @param CacheRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function update(CacheRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->settings->cache($request->validated(), (int) $request->user()->id, ! $request->routeIs('admin.settings.cache.test'));
        if ($request->expectsJson()) {
            return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
        }

        if (! $result->ok) {
            throw ValidationException::withMessages(['settings' => $result->message]);
        }

        return redirect()->route('admin.settings.cache.edit')->with('status', $result->message);
    }
}
