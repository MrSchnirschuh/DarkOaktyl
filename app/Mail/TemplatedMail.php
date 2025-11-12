<?php

namespace Everest\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TemplatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        protected string $subjectLine,
        protected string $htmlBody,
        protected ?string $plainBody = null
    ) {
        $this->subject($subjectLine);
    }

    public function build(): self
    {
        $mail = $this->html($this->htmlBody);

        if (!empty($this->plainBody)) {
            $mail->text('emails.plain', [
                'content' => $this->plainBody,
            ]);
        }

        return $mail;
    }
}
