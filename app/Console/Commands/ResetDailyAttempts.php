<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class ResetDailyAttempts extends Command
{
    protected $signature = 'orders:reset-daily-attempts';
    protected $description = 'Reset daily attempts for all orders';

    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        parent::__construct();
        $this->orderService = $orderService;
    }

    public function handle()
    {
        $this->info('Resetting daily attempts for all orders...');
        $this->orderService->resetDailyAttempts();
        $this->info('Daily attempts reset successfully.');
    }
}