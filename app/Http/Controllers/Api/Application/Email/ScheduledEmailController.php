<?php

namespace Everest\Http\Controllers\Api\Application\Email;

use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Models\ScheduledEmail;
use Everest\Transformers\Api\Application\ScheduledEmailTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduledEmailController extends ApplicationApiController
{
    /**
     * Return all scheduled emails.
     */
    public function index(): array
    {
        $scheduledEmails = ScheduledEmail::with('template')->get();

        return $this->fractal->collection($scheduledEmails)
            ->transformWith(new ScheduledEmailTransformer())
            ->toArray();
    }

    /**
     * Return a single scheduled email.
     */
    public function view(ScheduledEmail $scheduledEmail): array
    {
        $scheduledEmail->load('template');

        return $this->fractal->item($scheduledEmail)
            ->transformWith(new ScheduledEmailTransformer())
            ->toArray();
    }

    /**
     * Create a new scheduled email.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'template_key' => 'required|string|exists:email_templates,key',
            'trigger_type' => 'required|string|in:cron,date,event',
            'trigger_value' => 'nullable|string',
            'event_name' => 'nullable|string',
            'recipients' => 'nullable|array',
            'template_data' => 'nullable|array',
            'enabled' => 'boolean',
        ]);

        $scheduledEmail = ScheduledEmail::create($validated);
        $scheduledEmail->load('template');

        return new JsonResponse(
            $this->fractal->item($scheduledEmail)
                ->transformWith(new ScheduledEmailTransformer())
                ->toArray(),
            201
        );
    }

    /**
     * Update an existing scheduled email.
     */
    public function update(Request $request, ScheduledEmail $scheduledEmail): array
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'template_key' => 'sometimes|string|exists:email_templates,key',
            'trigger_type' => 'sometimes|string|in:cron,date,event',
            'trigger_value' => 'nullable|string',
            'event_name' => 'nullable|string',
            'recipients' => 'nullable|array',
            'template_data' => 'nullable|array',
            'enabled' => 'boolean',
        ]);

        $scheduledEmail->update($validated);
        $scheduledEmail->load('template');

        return $this->fractal->item($scheduledEmail->fresh())
            ->transformWith(new ScheduledEmailTransformer())
            ->toArray();
    }

    /**
     * Delete a scheduled email.
     */
    public function destroy(ScheduledEmail $scheduledEmail): JsonResponse
    {
        $scheduledEmail->delete();

        return new JsonResponse([], 204);
    }
}
