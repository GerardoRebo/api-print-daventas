<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('plans:check-expired')->daily();
        // $schedule->command('lifecycle:run')->dailyAt('08:00'); // Ajusta a tu zona horaria
        $schedule->command('app:renew-timbres')->monthlyOn(1, '03:00');

        // Notificaciones de entregas por Telegram
        // 10 AM - Notificar entregas de hoy
        $schedule->command('notifications:send-delivery today')->dailyAt('10:00');

        // 7 PM - Notificar entregas de mañana
        $schedule->command('notifications:send-delivery tomorrow')->dailyAt('19:00');
    }
    protected $commands = [];

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
