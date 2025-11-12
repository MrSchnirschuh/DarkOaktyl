<?php

namespace DarkOak\Listeners\Emails;

use DarkOak\Models\EmailTrigger;
use DarkOak\Services\Emails\EmailTriggerProcessor;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmailTriggerEventSubscriber
{
    public function __construct(private EmailTriggerProcessor $processor)
    {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('*', function (string $event, array $payload) {
            $this->handle($event, $payload);
        });
    }

    protected function handle(string $event, array $payload): void
    {
        if (!config('modules.email.enabled', false)) {
            return;
        }

        if ($this->shouldSkipEvent($event)) {
            return;
        }

        if (!$this->triggersTableExists()) {
            return;
        }

        $triggers = EmailTrigger::query()
            ->where('trigger_type', EmailTrigger::TYPE_EVENT)
            ->where('is_active', true)
            ->where('event_key', $event)
            ->get();

        if ($triggers->isEmpty()) {
            return;
        }

        $context = $this->normalizePayload($event, $payload);

        foreach ($triggers as $trigger) {
            $this->processor->process($trigger, $context);
        }
    }

    protected function normalizePayload(string $event, array $payload): array
    {
        $context = ['event' => $event];
        $instance = $payload[0] ?? null;

        if (is_object($instance)) {
            foreach (['user', 'server', 'order', 'coupon', 'resources'] as $property) {
                if (property_exists($instance, $property)) {
                    $context[$property] = $instance->{$property};
                }
            }
        }

        return $context;
    }

    private function shouldSkipEvent(string $event): bool
    {
        return Str::startsWith($event, [
            'Illuminate\\',
            'artisan.start',
            'artisan.finish',
        ]);
    }

    private static bool $checkingSchema = false;

    private static ?bool $triggersTableExists = null;

    private function triggersTableExists(): bool
    {
        if (self::$triggersTableExists !== null) {
            return self::$triggersTableExists;
        }

        if (self::$checkingSchema) {
            return false;
        }

        self::$checkingSchema = true;

        try {
            self::$triggersTableExists = Schema::hasTable('email_triggers');
        } catch (\Throwable $exception) {
            self::$triggersTableExists = false;
        } finally {
            self::$checkingSchema = false;
        }

        return self::$triggersTableExists;
    }
}

