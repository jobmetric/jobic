<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

/** Validate an isolated SMTP diagnostic without saving configuration. */
class MailTestRequest extends MailRequest
{
    /**
     * Require SMTP and a recipient only when sending a test message.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'MAIL_MAILER' => [
                'required',
                'in:smtp',
            ],
            'action'      => [
                'required',
                'in:connection,send',
            ],
            'recipient'   => [
                'required_if:action,send',
                'nullable',
                'email',
                'max:254',
            ],
        ]);
    }
}
