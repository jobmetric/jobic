<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\MailRequest;
use App\Services\Settings\MailSettings;
use App\Support\Installation\Fields;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handle mail page requests.
 */
class MailController extends Controller
{
    /**
     * Initialize the service dependencies.
     *
     * @param MailSettings $settings
     *
     * @return void
     */
    public function __construct(private readonly MailSettings $settings) {}

    /**
     * Render the requested settings form.
     *
     * @return View
     */
    public function edit(): View
    {
        Page::prepare('settings');

        return view('settings.mail', ['fields' => Fields::mail(), 'values' => $this->settings->values()]);
    }

    /**
     * Save mail settings and return the service result.
     *
     * @param MailRequest $request
     *
     * @return JsonResponse|RedirectResponse
     */
    public function update(MailRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->settings->save($request->validated(), (int) $request->user()->id);
        if ($request->expectsJson()) {
            return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
        }
        if (! $result->ok) {
            throw ValidationException::withMessages(['settings' => $result->message]);
        }

        return redirect()->route('admin.settings.mail.edit')->with('status', $result->message);
    }
}
