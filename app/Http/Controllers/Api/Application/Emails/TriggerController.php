<?php

namespace Everest\Http\Controllers\Api\Application\Emails;

use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Everest\Facades\Activity;
use Everest\Models\EmailTemplate;
use Everest\Models\EmailTrigger;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\Emails\DeleteEmailTriggerRequest;
use Everest\Http\Requests\Api\Application\Emails\GetEmailTriggersRequest;
use Everest\Http\Requests\Api\Application\Emails\RunEmailTriggerRequest;
use Everest\Http\Requests\Api\Application\Emails\StoreEmailTriggerRequest;
use Everest\Http\Requests\Api\Application\Emails\UpdateEmailTriggerRequest;
use Everest\Services\Emails\EmailEventRegistry;
use Everest\Services\Emails\EmailTriggerProcessor;
use Everest\Transformers\Api\Application\Emails\EmailTriggerTransformer;
use Everest\Exceptions\Http\QueryValueOutOfRangeHttpException;

class TriggerController extends ApplicationApiController
{
    public function __construct(
        private EmailTriggerProcessor $processor,
        private EmailEventRegistry $events
    ) {
        parent::__construct();
    }

    public function index(GetEmailTriggersRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '50');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $triggers = QueryBuilder::for(EmailTrigger::query()->with('template'))
            ->allowedFilters([
                'name',
                AllowedFilter::exact('trigger_type'),
                AllowedFilter::exact('event_key'),
                AllowedFilter::exact('is_active'),
            ])
            ->allowedIncludes(['template'])
            ->allowedSorts(['name', 'trigger_type', 'next_run_at', 'created_at', 'updated_at'])
            ->paginate($perPage);

        return $this->fractal->collection($triggers)
            ->transformWith(EmailTriggerTransformer::class)
            ->toArray();
    }

    public function store(StoreEmailTriggerRequest $request): JsonResponse
    {
        $template = EmailTemplate::query()->where('uuid', $request->input('template_uuid'))->firstOrFail();

        $attributes = $this->buildTriggerPayload($request);
        $attributes['template_id'] = $template->id;

        $trigger = EmailTrigger::query()->create($attributes);

        Activity::event('admin:emails:triggers:create')
            ->property('trigger', $trigger)
            ->description('An email trigger was created')
            ->log();

        return $this->fractal->item($trigger->load('template'))
            ->transformWith(EmailTriggerTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function view(GetEmailTriggersRequest $request, EmailTrigger $trigger): array
    {
        $trigger->load('template');

        return $this->fractal->item($trigger)
            ->transformWith(EmailTriggerTransformer::class)
            ->toArray();
    }

    public function update(UpdateEmailTriggerRequest $request, EmailTrigger $trigger): array
    {
        $payload = $this->buildTriggerPayload($request, $trigger);

        if ($request->exists('template_uuid')) {
            $templateUuid = $request->input('template_uuid');
            $template = $templateUuid ? EmailTemplate::query()->where('uuid', $templateUuid)->firstOrFail() : null;
            $payload['template_id'] = $template?->id;
        }

        if (!empty($payload)) {
            $trigger->updateOrFail($payload);
        }

        Activity::event('admin:emails:triggers:update')
            ->property('trigger', $trigger)
            ->property('changes', $payload)
            ->description('An email trigger was updated')
            ->log();

        return $this->fractal->item($trigger->refresh('template'))
            ->transformWith(EmailTriggerTransformer::class)
            ->toArray();
    }

    public function delete(DeleteEmailTriggerRequest $request, EmailTrigger $trigger): Response
    {
        $trigger->delete();

        Activity::event('admin:emails:triggers:delete')
            ->property('trigger', $trigger)
            ->description('An email trigger was deleted')
            ->log();

        return $this->returnNoContent();
    }

    public function run(RunEmailTriggerRequest $request, EmailTrigger $trigger): Response
    {
        $trigger->loadMissing('template');

        $this->processor->process($trigger);
        $this->processor->updateNextRun($trigger);

        Activity::event('admin:emails:triggers:run')
            ->property('trigger', $trigger)
            ->description('An email trigger was executed manually')
            ->log();

        return $this->returnNoContent();
    }

    public function events(GetEmailTriggersRequest $request): array
    {
        return [
            'data' => $this->events->all(),
        ];
    }

    private function buildTriggerPayload(StoreEmailTriggerRequest|UpdateEmailTriggerRequest $request, ?EmailTrigger $existing = null): array
    {
        $payload = [];

        $fields = [
            'name',
            'description',
            'trigger_type',
            'schedule_type',
            'event_key',
            'cron_expression',
            'timezone',
        ];

        foreach ($fields as $field) {
            if (!$existing || $request->exists($field)) {
                $payload[$field] = $request->input($field, $existing?->{$field});
            }
        }

        if ($request->exists('schedule_at') || !$existing) {
            $payload['schedule_at'] = $request->input('schedule_at');
        }

        if ($request->exists('payload') || !$existing) {
            $payload['payload'] = $this->normalizePayload($request->input('payload'));
        }

        if (!$existing) {
            $payload['is_active'] = $request->exists('is_active') ? $request->boolean('is_active') : true;
        } elseif ($request->exists('is_active')) {
            $payload['is_active'] = $request->boolean('is_active');
        }

    $payload = $this->prepareScheduleAttributes($payload, $existing);

    $this->assertEventKeyRequirement($payload, $existing);

    return $payload;
    }

    private function prepareScheduleAttributes(array $payload, ?EmailTrigger $existing = null): array
    {
        $timezone = $payload['timezone'] ?? $existing?->timezone ?? config('app.timezone', 'UTC');
        $payload['timezone'] = $timezone;

        $triggerType = $payload['trigger_type'] ?? $existing?->trigger_type ?? EmailTrigger::TYPE_EVENT;
        $payload['trigger_type'] = $triggerType;

        if ($triggerType !== EmailTrigger::TYPE_SCHEDULE) {
            $payload['schedule_type'] = EmailTrigger::SCHEDULE_ONCE;
            $payload['schedule_at'] = null;
            $payload['cron_expression'] = null;
            $payload['next_run_at'] = null;

            return $payload;
        }

        $scheduleType = $payload['schedule_type'] ?? $existing?->schedule_type ?? EmailTrigger::SCHEDULE_ONCE;
        $payload['schedule_type'] = $scheduleType;

        $scheduleAt = $payload['schedule_at'] ?? $existing?->schedule_at;
        $cronExpression = $payload['cron_expression'] ?? $existing?->cron_expression;

        if ($scheduleType === EmailTrigger::SCHEDULE_RECURRING) {
            if (empty($cronExpression) || !CronExpression::isValidExpression($cronExpression)) {
                throw ValidationException::withMessages([
                    'cron_expression' => 'The provided cron expression is invalid.',
                ]);
            }

            $payload['cron_expression'] = $cronExpression;
            $nextRun = $this->nextRunFromCron($cronExpression, $timezone);
            $payload['schedule_at'] = $this->normalizeScheduleAt($scheduleAt, $timezone) ?? $nextRun;
            $payload['next_run_at'] = $nextRun;
        } else {
            $payload['cron_expression'] = null;
            $payload['schedule_at'] = $this->normalizeScheduleAt($scheduleAt, $timezone);
            $payload['next_run_at'] = $payload['schedule_at'];
        }

        return $payload;
    }

    private function normalizeScheduleAt(mixed $value, string $timezone): ?CarbonImmutable
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($value)->setTimezone('UTC');
        }

        return CarbonImmutable::parse($value, $timezone)->setTimezone('UTC');
    }

    private function nextRunFromCron(string $expression, string $timezone): CarbonImmutable
    {
        $next = (new CronExpression($expression))->getNextRunDate(CarbonImmutable::now($timezone)->toDateTime());

        return CarbonImmutable::instance($next)->setTimezone('UTC');
    }

    private function normalizePayload(mixed $payload): ?array
    {
        if (is_null($payload)) {
            return null;
        }

        $array = is_array($payload) ? $payload : Arr::wrap($payload);

        return empty($array) ? null : $array;
    }

    private function assertEventKeyRequirement(array $payload, ?EmailTrigger $existing): void
    {
        $triggerType = $payload['trigger_type'] ?? $existing?->trigger_type;
        if ($triggerType !== EmailTrigger::TYPE_EVENT) {
            return;
        }

        $eventKey = $payload['event_key'] ?? $existing?->event_key;
        if (empty($eventKey)) {
            throw ValidationException::withMessages([
                'event_key' => 'An event key is required when using event triggers.',
            ]);
        }
    }
}
