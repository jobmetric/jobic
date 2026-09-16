<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Facades\Hooks;
use App\Http\Requests\Settings\MailRequest;
use App\Support\Installation\Environment;
use App\Support\Installation\Fields;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use Throwable;

/**
 * Manage outgoing mail configuration.
 */
readonly class MailSettings
{
    /**
     * Initialize the service dependencies.
     *
     * @param Environment $environment
     *
     * @return void
     */
    public function __construct(private Environment $environment)
    {
    }

    /**
     * Get mail configuration values without exposing stored credentials.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return Fields::values(Fields::mail(), $this->environment->read());
    }

    /**
     * Validate and save mail settings, preserving an omitted password.
     *
     * @param array<string, mixed> $data
     * @param int|null $userId
     *
     * @return Response
     * @throws ValidationException
     * @throws Throwable
     */
    public function save(array $data, ?int $userId = null): Response
    {
        $validated = dto($data, MailRequest::class);
        if (empty($validated['MAIL_PASSWORD'])) {
            unset($validated['MAIL_PASSWORD']);
        }
        $context = ['section' => 'mail', 'user_id' => $userId, 'fields' => array_keys($validated)];
        Hooks::action('jobic.settings.mail.saving', $context);
        try {
            $this->environment->write($validated + ['MAIL_URL' => null]);

            Hooks::notify('jobic.settings.mail.saved', $context);

            return Response::make(true, trans('installation.saved'));
        } catch (Throwable) {
            return Response::make(false, trans('installation.save_failed'), null, 422);
        }
    }
}
