<?php

namespace App\Plugins\ShadowStore\Notifications;

use App\Plugins\ShadowStore\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderBillingStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Order $order,
        private readonly string $type,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $orderNumber = $this->order->order_number;
        $productName = $this->order->product?->name ?? 'Server Hosting';
        $dueAt = $this->order->bill_due_at?->toDateTimeString() ?? 'N/A';

        return match ($this->type) {
            'due' => (new MailMessage())
                ->subject('Invoice Due: ' . $orderNumber)
                ->greeting('Hello ' . ($notifiable->username ?? 'there') . ',')
                ->line('Your bill for ' . $productName . ' is due soon.')
                ->line('Order: ' . $orderNumber)
                ->line('Due date: ' . $dueAt)
                ->line('Please make payment by the due date to avoid service interruption.')
                ->action('View Orders', url('/store/orders')),

            'past_due' => (new MailMessage())
                ->subject('Invoice Past Due: ' . $orderNumber)
                ->greeting('Hello ' . ($notifiable->username ?? 'there') . ',')
                ->line('Your bill for ' . $productName . ' is now past due.')
                ->line('Order: ' . $orderNumber)
                ->line('Due date: ' . $dueAt)
                ->line('If payment is not received within 2 days of the due date, your server will be automatically suspended.')
                ->action('View Orders', url('/store/orders')),

            default => (new MailMessage())
                ->subject('Server Suspended For Non-Payment: ' . $orderNumber)
                ->greeting('Hello ' . ($notifiable->username ?? 'there') . ',')
                ->line('Your server has been suspended due to non-payment.')
                ->line('Order: ' . $orderNumber)
                ->line('Due date: ' . $dueAt)
                ->line('Please complete payment to restore service.')
                ->action('View Orders', url('/store/orders')),
        };
    }
}
