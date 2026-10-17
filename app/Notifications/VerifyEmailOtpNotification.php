<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class VerifyEmailOtpNotification extends Notification
{
    use Queueable;

    public function __construct(public string $otp)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'CredAI');

        return (new MailMessage)
            ->subject(Lang::get('Email Verification Code - :app', ['app' => $appName]))
            ->greeting(Lang::get('Hello :name,', ['name' => $notifiable->name ?? 'User']))
            ->line(Lang::get('Your verification code (OTP) for your :app account is:', ['app' => $appName]))
            ->line('**'.$this->otp.'**')
            ->line(Lang::get('This code will expire in 15 minutes.'))
            ->line(Lang::get('If you did not request this verification code, please ignore this email.'))
            ->salutation(Lang::get("Regards,\nThe :app Team", ['app' => $appName]));
    }
}

