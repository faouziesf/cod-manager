<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDated extends Notification
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'La commande #' . $this->order->id . ' a été programmée pour le ' . $this->order->scheduled_date->format('d/m/Y') . '.',
            'order_id' => $this->order->id,
            'action' => 'dated',
            'scheduled_date' => $this->order->scheduled_date->format('Y-m-d'),
        ];
    }
}