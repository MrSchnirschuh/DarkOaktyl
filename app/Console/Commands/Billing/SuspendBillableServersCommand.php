<?php

namespace DarkOak\Console\Commands\Billing;

use DarkOak\Models\Server;
use Illuminate\Console\Command;

class SuspendBillableServersCommand extends Command
{
    protected $description = 'An automated task to suspend billable servers with past renewal dates.';

    protected $signature = 'p:billing:suspend-billable-servers';

    /**
     * Handle command execution.
     */
    public function handle()
    {
        $suspension = $this->getLaravel()->make(\DarkOak\Services\Servers\SuspensionService::class);
        $deletion = $this->getLaravel()->make(\DarkOak\Services\Servers\ServerDeletionService::class);

        foreach (Server::whereNotNull('renewal_date')->get() as $server) {
            $daysOverdue = $server->renewal_date->diffInDays(now());
            $threshold = config('modules.billing.renewal.threshold');

            if ($server->renewal_date->isPast()) {
                if (!$server->isSuspended()) {
                    $this->info("suspending server {$server->id}, overdue by {$daysOverdue} days");
                    $suspension->toggle($server, 'suspend');
                } elseif ($daysOverdue > $threshold) {
                    $this->info("deleting server {$server->id}, overdue by {$daysOverdue} days");
                    $deletion->withForce(true)->handle($server);
                }
            }
        }
    }
}
