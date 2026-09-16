<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AppearanceRequest;
use App\Http\Requests\Settings\GeneralRequest;
use App\Http\Requests\Settings\ProfileRequest;
use App\Http\Requests\Settings\SecurityRequest;
use App\Services\Settings\Settings;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\PackageCore\Output\Response;

class SettingsController extends Controller
{
    public function __construct(private readonly Settings $settings) {}

    public function index(): View
    {
        Page::prepare('settings');

        return view('settings.index');
    }

    public function edit(Request $request, string $section): View
    {
        abort_unless(in_array($section, ['general', 'profile', 'security', 'appearance'], true), 404);

        Page::prepare('settings');

        return view('settings.'.$section, $this->settings->page($section, (int) $request->user()->id));
    }

    public function general(GeneralRequest $request): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->settings->general($request->validated(), (int) $request->user()->id), 'general');
    }

    public function profile(ProfileRequest $request): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->settings->profile($request->validated(), (int) $request->user()->id), 'profile');
    }

    public function security(SecurityRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->settings->security($request->validated(), (int) $request->user()->id);
        if ($result->ok) {
            $request->session()->regenerate();
        }

        return $this->respond($request, $result, 'security');
    }

    public function appearance(AppearanceRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->settings->appearance($request->validated(), (int) $request->user()->id);

        return $this->respond($request, $result, 'appearance');
    }

    private function respond(Request $request, Response $result, string $section): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
        }
        if (! $result->ok) {
            throw ValidationException::withMessages(['settings' => $result->message]);
        }

        return redirect()->route('admin.settings.edit', $section)->with('status', $result->message);
    }
}
