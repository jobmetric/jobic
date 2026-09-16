<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Provide reset password notification application behavior.
 */
class ResetPasswordNotification extends ResetPassword
{
    /**
     * Build the password reset email notification.
     *
     * @param object $notifiable
     *
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)->subject(__('auth.reset_mail_subject'))
            ->line(__('auth.reset_mail_line1'))
            ->action(__('auth.reset_mail_action'), $url)
            ->line(__('auth.reset_mail_line2', ['count' => config('auth.passwords.users.expire')]))
            ->line(__('auth.reset_mail_line3'));
    }
}
