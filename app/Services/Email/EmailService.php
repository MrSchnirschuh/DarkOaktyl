<?php

namespace Everest\Services\Email;

use Everest\Mail\CustomTemplateMail;
use Everest\Models\EmailTemplate;
use Everest\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    /**
     * Send an email using a template.
     *
     * @param string $templateKey
     * @param string|User $recipient
     * @param array $data
     * @return bool
     */
    public function sendTemplatedEmail(string $templateKey, $recipient, array $data = []): bool
    {
        $template = EmailTemplate::where('key', $templateKey)
            ->where('enabled', true)
            ->first();

        if (!$template) {
            return false;
        }

        $email = $recipient instanceof User ? $recipient->email : $recipient;

        try {
            Mail::to($email)->send(new CustomTemplateMail($template, $data));
            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }

    /**
     * Send an email to multiple recipients.
     *
     * @param string $templateKey
     * @param array $recipients
     * @param array $data
     * @return int Number of successfully sent emails
     */
    public function sendBulkTemplatedEmail(string $templateKey, array $recipients, array $data = []): int
    {
        $successCount = 0;

        foreach ($recipients as $recipient) {
            if ($this->sendTemplatedEmail($templateKey, $recipient, $data)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Get all available template variables for a given template.
     *
     * @param string $templateKey
     * @return array
     */
    public function getTemplateVariables(string $templateKey): array
    {
        $template = EmailTemplate::where('key', $templateKey)->first();

        return $template ? ($template->variables ?? []) : [];
    }
}
