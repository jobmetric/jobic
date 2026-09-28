<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\DynamicSettingRequest;
use App\Services\Settings\DynamicSettings;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use Throwable;

/**
 * Handle generic pages for registered setting forms.
 */
class DynamicSettingController extends Controller
{
    /**
     * Create the controller with its domain service.
     *
     * @param DynamicSettings $settings
     *
     * @return void
     */
    public function __construct(private DynamicSettings $settings)
    {
    }

    /**
     * Render a discovered settings form.
     *
     * @param Request $request
     * @param string $application
     * @param string $key
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function edit(Request $request, string $application, string $key): View
    {
        Page::prepare('dynamic-settings');

        $setting = $this->settings->resolve($application, $key);

        Page::addSettingAssets($setting);

        $builder = $this->settings->builder($setting)->action(route('admin.settings.forms.update', [
            $application,
            $key,
        ]))->method('PUT')->class('cms-settings-form')->id('dynamic-settings-form');

        if (collect($builder->build()->getAllCustomFields())->contains(fn ($field
        ): bool => $field->type === 'file' || (bool) ($field->params['managed_file'] ?? false))) {
            $builder->enctype('multipart/form-data');
        }

        return view('settings.dynamic', [
            'section' => 'dynamic-' . $setting->formName(),
            'setting' => $setting,
            'form'    => $builder->build(),
            'values'  => $this->settings->values($setting, $builder),
        ]);
    }

    /**
     * Save a discovered settings form without replacing the document.
     *
     * @param DynamicSettingRequest $request
     * @param string $application
     * @param string $key
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function update(
        DynamicSettingRequest $request,
        string $application,
        string $key
    ): JsonResponse|RedirectResponse {
        $setting = $this->settings->resolve($application, $key);
        $result = $this->settings->save($setting, $request->builder(), $request->validated(), (int) $request->user()->id);
        if ($request->expectsJson()) {
            return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
        }

        return redirect()->route('admin.settings.forms.edit', [$application, $key])->with('status', $result->message);
    }
}
