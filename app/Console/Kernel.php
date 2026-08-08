<?php

namespace DarkOak\Console;

use DarkOak\Models\ActivityLog;
use DarkOak\Models\JGuardDelay;
use DarkOak\Models\JGuardAttempt;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Console\PruneCommand;
use DarkOak\Console\Commands\AutoUpdateCommand;
use DarkOak\Console\Commands\Billing\CleanupOrdersCommand;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use DarkOak\Console\Commands\Billing\GenerateInvoicesCommand;
use DarkOak\Console\Commands\Schedule\ProcessRunnableCommand;
use DarkOak\Console\Commands\Billing\SuspendBillableServersCommand;
use DarkOak\Console\Commands\Maintenance\PruneOrphanedBackupsCommand;
use DarkOak\Console\Commands\Billing\CalculateOrderThreatIndexCommand;
use DarkOak\Console\Commands\Maintenance\CleanServiceBackupFilesCommand;

class Kernel extends ConsoleKernel
{
    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // https://laravel.com/docs/10.x/upgrade#redis-cache-tags
        $schedule->command('cache:prune-stale-tags')->hourly();

        // Execute scheduled commands for servers every minute, as if there was a normal cron running.
        $schedule->command(ProcessRunnableCommand::class)->everyMinute()->withoutOverlapping();
        $schedule->command(CleanServiceBackupFilesCommand::class)->daily();

        if (config('backups.prune_age')) {
            // Every 30 minutes, run the backup pruning command so that any abandoned backups can be deleted.
            $schedule->command(PruneOrphanedBackupsCommand::class)->everyThirtyMinutes();
        }

        if (config('activity.prune_days')) {
            $schedule->command(PruneCommand::class, ['--model' => [ActivityLog::class]])->daily();
        }

        // jGuard only needs to retain IP-linked attempt/delay records long enough to
        // evaluate its own sensitivity window; prune anything older than that daily so
        // we don't hold on to identifying customer data (IP addresses) longer than needed.
        $schedule->command(PruneCommand::class, ['--model' => [JGuardAttempt::class, JGuardDelay::class]])->daily();

        if (config('app.auto_update')) {
            $schedule->command(AutoUpdateCommand::class)->daily();
        }

        if (config('modules.billing.enabled')) {
            $schedule->command(CleanupOrdersCommand::class)->daily();
            $schedule->command(SuspendBillableServersCommand::class)->daily();
            $schedule->command(CalculateOrderThreatIndexCommand::class)->everyFiveMinutes();
            $schedule->command(GenerateInvoicesCommand::class)->everyFiveMinutes();
        }
    }
}
