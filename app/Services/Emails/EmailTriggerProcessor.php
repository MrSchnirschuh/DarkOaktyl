<?php

namespace DarkOak\Services\Emails;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use DarkOak\Models\EmailTrigger;
use DarkOak\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EmailTriggerProcessor
{
    public function __construct(protected EmailDispatchService $dispatch)
    {
    }

    public function process(EmailTrigger $trigger, array $context = []): void
    {
        $template = $trigger->template()->first();
        if (!$template || !$template->is_enabled) {
            return;
        }

        $payload = $trigger->payload ?? [];
        $audience = Arr::get($payload, 'audience', ['type' => 'all_users']);
        $data = Arr::get($payload, 'data', []);

        $recipients = $this->resolveAudience($audience, $context);
        if ($recipients->isEmpty()) {
            return;
        }

        $this->dispatch->send($template, $recipients, array_merge($data, $context));
    }

    public function updateNextRun(EmailTrigger $trigger): void
    {
        if ($trigger->trigger_type !== EmailTrigger::TYPE_SCHEDULE) {
            return;
        }

        $now = CarbonImmutable::now($trigger->timezone);

        if ($trigger->schedule_type === EmailTrigger::SCHEDULE_RECURRING && !empty($trigger->cron_expression)) {
            $cron = new CronExpression($trigger->cron_expression);
            $trigger->next_run_at = CarbonImmutable::instance($cron->getNextRunDate($now->toDateTime()))
                ->shiftTimezone('UTC');
        } else {
            $trigger->next_run_at = null;
            $trigger->is_active = false;
        }

        $trigger->last_run_at = $now->shiftTimezone('UTC');
        $trigger->save();
    }

    protected function resolveAudience(array $audience, array $context = []): Collection
    {
        return match (Arr::get($audience, 'type', 'all_users')) {
            'specific_users' => $this->resolveSpecificUsers($audience),
            'specific_emails' => collect(Arr::get($audience, 'emails', []))->filter(),
            'event_recipient' => $this->resolveEventRecipient($context),
            'admins' => User::query()->where('root_admin', true)->get(),
            default => User::all(),
        };
    }

    protected function resolveSpecificUsers(array $audience): Collection
    {
        $ids = Arr::get($audience, 'ids', []);
        if (empty($ids)) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->get();
    }

    protected function resolveEventRecipient(array $context): Collection
    {
        $entities = collect([
            Arr::get($context, 'user'),
            Arr::get($context, 'server.owner'),
            Arr::get($context, 'order.user'),
        ])->filter(fn ($value) => $value instanceof User);

        return $entities->unique(fn (User $user) => $user->id);
    }
}

