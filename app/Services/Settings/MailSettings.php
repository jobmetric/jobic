<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Facades\Hooks;
use App\Http\Requests\Settings\MailRequest;
use App\Http\Requests\Settings\MailTestRequest;
use App\Support\Installation\Environment;
use App\Support\Installation\Fields;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
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
     * Test unsaved SMTP values using a separate transport and bounded socket timeout.
     *
     * @param array<string, mixed> $data
     *
     * @return Response
     * @throws ValidationException
     * @throws Throwable
     */
    public function test(array $data): Response
    {
        $values = dto($data, MailTestRequest::class);
        $transport = null;
        try {
            $transport = new EsmtpTransport($values['MAIL_HOST'], (int) $values['MAIL_PORT'], $values['MAIL_SCHEME'] === 'smtps');
            $transport->getStream()
                ->setTimeout(5);
            $transport->setUsername($values['MAIL_USERNAME'] ?? '');
            $storedPassword = $this->environment->read()['MAIL_PASSWORD'] ?? '';
            $storedPassword = in_array($storedPassword, [
                'null',
                '(null)',
            ], true) ? '' : $storedPassword;
            $transport->setPassword(($values['MAIL_PASSWORD'] ?? '') ?: $storedPassword);
            $transport->start();

            if ($values['action'] === 'send') {
                $message = (new Email)->from(new Address($values['MAIL_FROM_ADDRESS'], $values['MAIL_FROM_NAME']))
                    ->to($values['recipient'])
                    ->subject(trans('system.test_subject'))
                    ->text(trans('system.test_body'))
                    ->html(view('mail.test', ['senderName' => $values['MAIL_FROM_NAME']])->render());
                $transport->send($message);
            }

            return Response::make(true, trans('system.' . ($values['action'] === 'send' ? 'accepted' : 'connected')));
        } catch (Throwable) {
            return Response::make(false, trans('system.smtp_failed'), null, 422);
        } finally {
            try {
                $transport?->stop();
            } catch (Throwable) { /* Never leak SMTP conversation or credentials. */
            }
        }
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

        $context = [
            'section' => 'mail',
            'user_id' => $userId,
            'fields'  => array_keys($validated),
        ];

        Hooks::lifecycle('jobic.settings.mail.saving', $context);

        try {
            $outcomeBefore = $this->environment->read();
            $this->environment->write($validated + ['MAIL_URL' => null]);
            if ($outcomeBefore !== $this->environment->read()) {
                app(\JobMetric\EventSystem\Support\CommittedEventDispatcher::class)->dispatch(new \App\Events\Mail\MailUpdateEvent($userId));
            }

            Hooks::notify('jobic.settings.mail.saved', $context);

            return Response::make(true, trans('installation.saved'));
        } catch (Throwable) {
            return Response::make(false, trans('installation.save_failed'), null, 422);
        }
    }
}
