<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\OrderService;
use App\Services\ProductService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected $orderService;
    protected $productService;
    protected $userService;

    public function __construct(
        OrderService $orderService,
        ProductService $productService,
        UserService $userService
    ) {
        $this->orderService = $orderService;
        $this->productService = $productService;
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $this->userService->updateLastActivity($user);

        $period = $request->get('period', 'today');
        $statistics = $this->orderService->getOrderStatistics($user, $period);

        $data = [
            'statistics' => $statistics,
            'period' => $period,
        ];

        if ($user->isAdmin()) {
            $adminSettings = $user->settings;
            $lowStockProducts = $this->productService->checkLowStockProducts($user, 5);
            $ordersWithOutOfStock = $this->productService->getOrdersWithOutOfStockProducts($user);
            
            $data['managers'] = $this->userService->getUsersByRole($user, 'manager');
            $data['employees'] = $this->userService->getUsersByRole($user, 'employee');
            $data['lowStockProducts'] = $lowStockProducts;
            $data['ordersWithOutOfStock'] = $ordersWithOutOfStock;
            $data['settings'] = $adminSettings;
            $data['onlineUsers'] = $this->userService->getOnlineUsers($user);
            
            if ($user->trial_ends_at && $user->trial_ends_at->isFuture()) {
                $data['trialDaysLeft'] = $user->trial_ends_at->diffInDays(now());
            }
        } elseif ($user->isManager()) {
            $adminSettings = $user->admin->settings;
            $data['employees'] = $this->userService->getUsersByRole($user, 'employee');
            $data['settings'] = $adminSettings;
            $data['onlineUsers'] = $this->userService->getOnlineUsers($user);
        } else {
            $adminSettings = $user->admin->settings;
            $data['settings'] = $adminSettings;
        }

        $data['availableOrders'] = $this->orderService->getAvailableOrderCount($user, $adminSettings);

        return view('dashboard', $data);
    }
}