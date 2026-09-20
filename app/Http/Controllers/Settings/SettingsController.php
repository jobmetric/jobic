<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\AppearanceRequest;
use App\Http\Requests\Settings\AvatarRequest;
use App\Http\Requests\Settings\GeneralRequest;
use App\Http\Requests\Settings\ProfileRequest;
use App\Http\Requests\Settings\SecurityRequest;
use App\Http\Requests\Settings\TwoFactorRequest;
use App\Services\Auth\TwoFactor;
use App\Services\Settings\Settings;
use App\Support\Page;
use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use JobMetric\PackageCore\Output\Response;
use Throwable;

/**
 * Handle settings page requests.
 */
class SettingsController extends Controller
{
    /**
     * Initialize the service dependencies.
     *
     * @return void
     */
    public function __construct(private readonly Settings $settings)
    {
    }

    /**
     * Render the settings directory.
     *
     * @throws SetPluginNotFoundException
     */
    public function index(): View
    {
        Page::prepare('settings');

        return view('settings.index');
    }

    /**
     * Render the requested settings form.
     *
     * @param Request $request
     * @param string $section
     *
     * @return View
     * @throws SetPluginNotFoundException
     * @throws Throwable
     * @throws DateInvalidTimeZoneException
     * @throws DateMalformedStringException
     */
    public function edit(Request $request, string $section): View
    {
        abort_unless(in_array($section, [
            'general',
            'profile',
            'security',
            'appearance',
        ], true), 404);

        Page::prepare('settings');

        $data = $this->settings->page($section, (int) $request->user()->id);
        if ($section === 'profile') {
            Domi::addModal('avatar-picker', 'admin.avatar_modal_title', view('settings.partials.avatar-picker', $data)->render(), '', [
                'size'       => 'lg',
                'centered'   => true,
                'scrollable' => true,
            ]);
        }

        return view('settings.' . $section, $data);
    }

    /**
     * Save website settings and return the service result.
     *
     * @param GeneralRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function general(GeneralRequest $request): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->settings->general($request->validated(), (int) $request->user()->id), 'general');
    }

    /**
     * Save profile settings for the authenticated account.
     *
     * @param ProfileRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function profile(ProfileRequest $request): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->settings->profile($request->validated(), (int) $request->user()->id), 'profile');
    }

    /**
     * Immediately save the authenticated account avatar selection.
     *
     * @param AvatarRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function avatar(AvatarRequest $request): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->settings->avatar($request->validated(), (int) $request->user()->id), 'profile');
    }

    /**
     * Change the account password and regenerate the session.
     *
     * @param SecurityRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function security(SecurityRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->settings->security($request->validated(), (int) $request->user()->id);
        if ($result->ok) {
            $request->session()->regenerate();
        }

        return $this->respond($request, $result, 'security');
    }

    /**
     * Save authenticator settings and render the next in-place form state.
     *
     * @param TwoFactorRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function twoFactor(TwoFactorRequest $request): JsonResponse|RedirectResponse
    {
        $result = app(TwoFactor::class)->update($request->validated(), (int) $request->user()->id);
        $state = $result->data;
        if ($request->expectsJson()) {
            $response = Response::make(true, $result->message, [
                'html' => view('settings.partials.two-factor', [
                    'state' => $state,
                ])->render(),
            ]);

            return response()
                ->json($response)
                ->header('Cache-Control', 'no-store')
                ->header('X-CSRF-TOKEN', csrf_token());
        }

        return redirect()
            ->route('admin.settings.edit', 'security')
            ->with('two_factor_state', $state)
            ->with('status', $result->message);
    }

    /**
     * Save appearance preferences for the authenticated account.
     *
     * @param AppearanceRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function appearance(AppearanceRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->settings->appearance($request->validated(), (int) $request->user()->id);

        return $this->respond($request, $result, 'appearance');
    }

    /**
     * Convert the service result into a JSON response or redirect.
     *
     * @param Request $request
     * @param Response $result
     * @param string $section
     *
     * @return JsonResponse|RedirectResponse
     */
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
