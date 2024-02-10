<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\Project;
use App\Models\ProjectPayment;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            $projects = Project::where('project_status', 'ACTIVE')
            ->where('project_type', 'RECURRING')
            ->get();
            
            foreach ($projects as $project) {
                $last_payment = $project->payments()->orderByDesc('due_date')->first();
                
                if (!$last_payment || Carbon::parse($last_payment->due_date)->addMonth()->isPast()) {
                    $amount = $project->budget;
                    
                    $payment = new ProjectPayment();
                    $payment->project_id = $project->id;
                    $payment->due_date = Carbon::now()->startOfMonth();
                    $payment->amount = $amount;
                    $payment->status = 'unpaid';
                    $payment->save();
                }
            }
        })->monthly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
