<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class ClearOldNotifications extends Command
{
    protected $signature = 'notifications:clear-old {days=10}';
    protected $description = 'Clear old notifications older than X days';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    public function handle()
    {
        $days = $this->argument('days');
        $this->info("Clearing notifications older than {$days} days...");
        $this->notificationService->clearOldNotifications($days);
        $this->info('Old notifications cleared successfully.');
    }
}