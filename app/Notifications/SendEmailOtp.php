<?php

namespace Pterodactyl\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendEmailOtp extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $code)
    {
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Your Login Verification Code')
            ->greeting('Hello ' . $notifiable->name_first . '!')
            ->line('Use the following code to complete your login:')
            ->line('# ' . $this->code)
            ->line('This code expires in **10 minutes** and can only be used once.')
            ->line('If you did not attempt to log in, please change your password immediately.');
    }
}
