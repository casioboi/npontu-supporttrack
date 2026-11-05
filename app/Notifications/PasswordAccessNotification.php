<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordAccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $token,
        private readonly bool $invitation,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $minutes = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject($this->invitation ? 'Your SupportTrack invitation' : 'Reset your SupportTrack password')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->invitation
                ? 'An administrator invited you to join the SupportTrack workspace.'
                : 'We received a request to reset the password for your SupportTrack account.')
            ->action($this->invitation ? 'Accept invitation and set password' : 'Reset password', $url)
            ->line("This secure link expires in {$minutes} minutes and can only be used once.")
            ->line('If you did not expect this message, you can safely ignore it.');
    }
}
