<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderAssigned;
use App\Notifications\OrderConfirmed;
use App\Notifications\OrderCreated;
use App\Notifications\OrderDated;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    public function notifyOrderCreated(Order $order)
    {
        $admin = $order->admin;
        $admin->notify(new OrderCreated($order));

        if ($order->assigned_to) {
            $assignee = $order->assignedTo;
            $assignee->notify(new OrderCreated($order));
        }
    }

    public function notifyOrderAssigned(Order $order, User $previousAssignee = null)
    {
        $assignee = $order->assignedTo;
        if ($assignee) {
            $assignee->notify(new OrderAssigned($order));
        }

        if ($previousAssignee && $previousAssignee->id !== $assignee->id) {
            $previousAssignee->notify(new OrderAssigned($order, true));
        }
    }

    public function notifyOrderConfirmed(Order $order)
    {
        $admin = $order->admin;
        $admin->notify(new OrderConfirmed($order));

        if ($order->assigned_to) {
            $assignee = $order->assignedTo;
            $assignee->notify(new OrderConfirmed($order));
        }
    }

    public function notifyOrderDated(Order $order)
    {
        if ($order->assigned_to) {
            $assignee = $order->assignedTo;
            $assignee->notify(new OrderDated($order));
        }

        // Notify managers
        User::where('admin_id', $order->admin_id)
            ->where('role', 'manager')
            ->get()
            ->each(function ($manager) use ($order) {
                $manager->notify(new OrderDated($order));
            });
    }

    public function clearOldNotifications($days = 10)
    {
        $date = now()->subDays($days);
        DB::table('notifications')
            ->where('created_at', '<', $date)
            ->delete();
    }

    public function getUnreadNotificationsCount(User $user)
    {
        return $user->unreadNotifications()->count();
    }

    public function markAllAsRead(User $user)
    {
        $user->unreadNotifications->markAsRead();
    }
}