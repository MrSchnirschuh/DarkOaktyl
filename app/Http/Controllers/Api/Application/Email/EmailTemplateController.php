<?php

namespace Everest\Http\Controllers\Api\Application\Email;

use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Models\EmailTemplate;
use Everest\Transformers\Api\Application\EmailTemplateTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Fractal\Fractal;

class EmailTemplateController extends ApplicationApiController
{
    /**
     * Return all email templates.
     */
    public function index(): array
    {
        $templates = EmailTemplate::all();

        return $this->fractal->collection($templates)
            ->transformWith(new EmailTemplateTransformer())
            ->toArray();
    }

    /**
     * Return a single email template.
     */
    public function view(EmailTemplate $template): array
    {
        return $this->fractal->item($template)
            ->transformWith(new EmailTemplateTransformer())
            ->toArray();
    }

    /**
     * Create a new email template.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:email_templates,key|max:255',
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'enabled' => 'boolean',
        ]);

        $template = EmailTemplate::create($validated);

        return new JsonResponse(
            $this->fractal->item($template)
                ->transformWith(new EmailTemplateTransformer())
                ->toArray(),
            201
        );
    }

    /**
     * Update an existing email template.
     */
    public function update(Request $request, EmailTemplate $template): array
    {
        $validated = $request->validate([
            'key' => 'sometimes|string|unique:email_templates,key,' . $template->id . '|max:255',
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'body_html' => 'sometimes|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'enabled' => 'boolean',
        ]);

        $template->update($validated);

        return $this->fractal->item($template->fresh())
            ->transformWith(new EmailTemplateTransformer())
            ->toArray();
    }

    /**
     * Delete an email template.
     */
    public function destroy(EmailTemplate $template): JsonResponse
    {
        $template->delete();

        return new JsonResponse([], 204);
    }

    /**
     * Test an email template by sending it to a specific address.
     */
    public function test(Request $request, EmailTemplate $template): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'data' => 'nullable|array',
        ]);

        $emailService = app(\Everest\Services\Email\EmailService::class);
        $success = $emailService->sendTemplatedEmail(
            $template->key,
            $validated['email'],
            $validated['data'] ?? []
        );

        return new JsonResponse([
            'success' => $success,
            'message' => $success ? 'Test email sent successfully' : 'Failed to send test email',
        ], $success ? 200 : 500);
    }
}
