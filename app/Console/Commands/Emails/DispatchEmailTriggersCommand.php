<?php

namespace Everest\Console\Commands\Emails;

use Carbon\CarbonImmutable;
use Everest\Models\EmailTrigger;
use Everest\Services\Emails\EmailTriggerProcessor;
use Illuminate\Console\Command;

class DispatchEmailTriggersCommand extends Command
{
    protected $signature = 'emails:dispatch-triggers';

    protected $description = 'Process and dispatch scheduled email triggers.';

    public function __construct(private EmailTriggerProcessor $processor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!config('modules.email.enabled', false)) {
            return self::SUCCESS;
        }

        $now = CarbonImmutable::now('UTC');

        EmailTrigger::query()
            ->where('trigger_type', EmailTrigger::TYPE_SCHEDULE)
            ->where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNotNull('next_run_at')->where('next_run_at', '<=', $now)
                    ->orWhere(function ($inner) use ($now) {
                        $inner->whereNull('next_run_at')
                            ->whereNotNull('schedule_at')
                            ->where('schedule_at', '<=', $now);
                    });
            })
            ->orderBy('next_run_at')
            ->chunkById(50, function ($triggers) {
                /** @var EmailTrigger $trigger */
                foreach ($triggers as $trigger) {
                    $this->processor->process($trigger);
                    $this->processor->updateNextRun($trigger);
                }
            });

        return self::SUCCESS;
    }
}
