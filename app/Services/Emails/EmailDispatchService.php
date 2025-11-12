<?php

namespace DarkOak\Services\Emails;

use DarkOak\Mail\TemplatedMail;
use DarkOak\Models\EmailTemplate;
use DarkOak\Models\User;
use DarkOak\Services\Emails\AnonymousRecipient;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Collection;

/** @phpstan-type RawContext array<string, mixed> */

class EmailDispatchService
{
    public function __construct(
        protected EmailTemplateRenderer $renderer,
        protected Mailer $mailer
    ) {
    }

    /**
     * @param iterable<User|string> $recipients
     */
    public function send(EmailTemplate $template, iterable $recipients, array|callable $context = []): void
    {
        $collection = $recipients instanceof Collection ? $recipients : collect($recipients);

        $collection->filter()
            ->each(function ($recipient) use ($template, $context) {
                $user = $recipient instanceof User ? $recipient : null;
                $email = $recipient instanceof User ? $recipient->email : $recipient;

                if (empty($email)) {
                    return;
                }

                $resolvedContext = is_callable($context) ? $context($recipient) : $context;
                $resolvedContext = is_array($resolvedContext) ? $resolvedContext : [];

                $normalizedContext = $this->normalizeContext($resolvedContext, $user, $email);

                $rendered = $this->renderer->render($template, $normalizedContext);

                $this->mailer->to($email)->send(new TemplatedMail(
                    $rendered['subject'],
                    $rendered['html'],
                    $rendered['text']
                ));
            });
    }

    /**
     * @param RawContext $context
     *
     * @return RawContext
     */
    protected function normalizeContext(array $context, ?User $user, ?string $email): array
    {
        if (array_key_exists('user', $context)) {
            $context['user'] = $this->resolveUserContext($context['user'], $user, $email);
        } else {
            $context['user'] = $this->resolveUserContext(null, $user, $email);
        }

        return $context;
    }

    protected function resolveUserContext(mixed $value, ?User $user, ?string $email): mixed
    {
        if ($value instanceof User) {
            return $value;
        }

        if ($value instanceof AnonymousRecipient) {
            return $value;
        }

        if (is_array($value)) {
            $value = (object) $value;
        }

        if (is_object($value) && property_exists($value, 'email')) {
            return $value;
        }

        if ($user instanceof User) {
            return $user;
        }

        if (is_string($value) && filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return AnonymousRecipient::fromEmail($value);
        }

        if (!empty($email)) {
            return AnonymousRecipient::fromEmail($email);
        }

        return $value;
    }
}

