<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request)
    {
        $user = Auth::user();
        $this->notificationService->markAllAsRead($user);

        return back()->with('success', 'Toutes les notifications ont été marquées comme lues.');
    }

    public function getUnreadCount()
    {
        $user = Auth::user();
        $count = $this->notificationService->getUnreadNotificationsCount($user);

        return response()->json(['count' => $count]);
    }
}