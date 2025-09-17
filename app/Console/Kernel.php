<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\SendPendingEmails::class,
        Commands\CheckOverdueTasks::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Envoyer les emails en attente toutes les 5 minutes
        $schedule->command('emails:send-pending')
                 ->everyFiveMinutes()
                 ->withoutOverlapping();

        // Vérifier les tâches en retard tous les jours à 9h
        $schedule->command('tasks:check-overdue')
                 ->dailyAt('09:00');

        // Nettoyage des logs d'activité anciens (> 3 mois)
        $schedule->command('model:prune', ['--model' => 'App\\Models\\ActivityLog'])
                 ->monthly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
