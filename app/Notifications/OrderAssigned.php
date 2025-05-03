<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderAssigned extends Notification
{
    use Queueable;

    protected $order;
    protected $unassigned;

    public function __construct(Order $order, $unassigned = false)
    {
        $this->order = $order;
        $this->unassigned = $unassigned;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        if ($this->unassigned) {
            return [
                'message' => 'La commande #' . $this->order->id . ' a été désassignée de vous.',
                'order_id' => $this->order->id,
                'action' => 'unassigned',
            ];
        }

        return [
            'message' => 'La commande #' . $this->order->id . ' vous a été assignée.',
            'order_id' => $this->order->id,
            'action' => 'assigned',
        ];
    }
}