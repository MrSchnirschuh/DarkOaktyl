<?php

namespace Everest\Jobs;

use Carbon\Carbon;
use Cron\CronExpression;
use Everest\Models\ScheduledEmail;
use Everest\Models\User;
use Everest\Services\Email\EmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessScheduledEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        $now = Carbon::now();

        $scheduledEmails = ScheduledEmail::where('enabled', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', $now);
            })
            ->with('template')
            ->get();

        foreach ($scheduledEmails as $scheduled) {
            if (!$scheduled->template || !$scheduled->template->enabled) {
                continue;
            }

            $recipients = $this->resolveRecipients($scheduled->recipients);

            if (empty($recipients)) {
                continue;
            }

            $data = $scheduled->template_data ?? [];

            $emailService->sendBulkTemplatedEmail(
                $scheduled->template_key,
                $recipients,
                $data
            );

            // Update last run time
            $scheduled->last_run_at = $now;

            // Calculate next run time based on trigger type
            if ($scheduled->trigger_type === 'cron' && $scheduled->trigger_value) {
                try {
                    $cron = new CronExpression($scheduled->trigger_value);
                    $scheduled->next_run_at = Carbon::instance($cron->getNextRunDate());
                } catch (\Exception $e) {
                    report($e);
                    $scheduled->enabled = false;
                }
            } elseif ($scheduled->trigger_type === 'date' && $scheduled->trigger_value) {
                // For one-time date triggers, disable after execution
                $scheduled->enabled = false;
            }

            $scheduled->save();
        }
    }

    /**
     * Resolve recipient selection criteria to actual email addresses.
     *
     * @param array|null $criteria
     * @return array
     */
    protected function resolveRecipients(?array $criteria): array
    {
        if (!$criteria) {
            return [];
        }

        $type = $criteria['type'] ?? 'all';
        $emails = [];

        switch ($type) {
            case 'all':
                $emails = User::pluck('email')->toArray();
                break;
            case 'specific':
                $emails = $criteria['emails'] ?? [];
                break;
            case 'role':
                $role = $criteria['role'] ?? null;
                if ($role === 'admin') {
                    $emails = User::where('root_admin', true)->pluck('email')->toArray();
                }
                break;
            case 'servers':
                // Users with servers
                $emails = User::whereHas('servers')->pluck('email')->toArray();
                break;
        }

        return array_filter($emails);
    }
}
