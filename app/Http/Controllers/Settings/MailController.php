<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\MailRequest;
use App\Services\Settings\MailSettings;
use App\Support\Installation\Fields;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MailController extends Controller
{
    public function __construct(private readonly MailSettings $settings) {}

    public function edit(): View
    {
        return view('settings.mail', ['fields' => Fields::mail(), 'values' => $this->settings->values()]);
    }

    public function update(MailRequest $request): RedirectResponse
    {
        $result = $this->settings->save($request->validated());
        if (! $result->ok) {
            throw ValidationException::withMessages(['settings' => $result->message]);
        }

        return redirect()->route('admin.settings.mail.edit')->with('status', $result->message);
    }
}
