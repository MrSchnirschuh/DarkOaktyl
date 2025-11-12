<?php

namespace Everest\Services\Emails;

use Everest\Mail\TemplatedMail;
use Everest\Models\EmailTemplate;
use Everest\Models\User;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Collection;

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
    public function send(EmailTemplate $template, iterable $recipients, array $context = []): void
    {
        $collection = $recipients instanceof Collection ? $recipients : collect($recipients);

        $collection->filter()
            ->each(function ($recipient) use ($template, $context) {
                $user = $recipient instanceof User ? $recipient : null;
                $email = $recipient instanceof User ? $recipient->email : $recipient;

                if (empty($email)) {
                    return;
                }

                $rendered = $this->renderer->render($template, array_merge($context, [
                    'user' => $user,
                ]));

                $this->mailer->to($email)->send(new TemplatedMail(
                    $rendered['subject'],
                    $rendered['html'],
                    $rendered['text']
                ));
            });
    }
}
