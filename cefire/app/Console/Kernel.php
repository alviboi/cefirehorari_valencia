<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule. El mes de agosto y septiembre no se calcula el mes de deuda.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command("calcula:mesdeute")->monthly()->when(function () {
            $mes = date('m');
            if ($mes == 8 || $mes == 9) {
                return false;
            } else {
                return true;
            }
        }
    );
         //Run the task on the first day of every month at 00:00
        //$schedule->command("calcula:mesdeute")->everyMinute(); //Run the task on the first day of every month at 00:00

    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
