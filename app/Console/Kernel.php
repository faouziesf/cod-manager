<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Réinitialiser les tentatives quotidiennes à minuit
        $schedule->command('orders:reset-daily-attempts')->daily();
        
        // Désactiver les comptes d'essai expirés tous les jours à 1h du matin
        $schedule->command('users:deactivate-expired-trials')->dailyAt('01:00');
        
        // Nettoyer les notifications anciennes tous les lundis à 2h du matin
        $schedule->command('notifications:clear-old')->weekly()->mondays()->at('02:00');
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}