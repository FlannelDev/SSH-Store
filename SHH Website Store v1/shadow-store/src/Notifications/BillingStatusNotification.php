<?php

namespace App\Plugins\ShadowStore\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BillingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $subject,
        private readonly string $body,
        private readonly string $actionUrl,
        private readonly string $actionText = 'Open Panel',
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage())
            ->subject($this->subject)
            ->greeting('Hello ' . ($notifiable->username ?? 'there') . ',');

        foreach (preg_split('/\r\n|\r|\n/', trim($this->body)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $message->line($line);
        }

        return $message->action($this->actionText, $this->actionUrl);
    }
}
