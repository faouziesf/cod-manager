<?php

namespace App\Console\Commands;

use App\Services\UserService;
use Illuminate\Console\Command;

class DeactivateExpiredTrials extends Command
{
    protected $signature = 'users:deactivate-expired-trials';
    protected $description = 'Deactivate expired trial accounts';

    protected $userService;

    public function __construct(UserService $userService)
    {
        parent::__construct();
        $this->userService = $userService;
    }

    public function handle()
    {
        $this->info('Deactivating expired trial accounts...');
        $count = $this->userService->deactivateExpiredTrials();
        $this->info("{$count} accounts deactivated.");
    }
}