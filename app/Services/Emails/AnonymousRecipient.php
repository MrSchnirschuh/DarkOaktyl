<?php

namespace Everest\Services\Emails;

class AnonymousRecipient
{
    public function __construct(
        public string $email,
        public string $username,
        public string $appearance_mode = 'system',
        public ?string $appearance_last_mode = null
    ) {
    }

    public static function fromEmail(string $email): self
    {
        $local = strstr($email, '@', true) ?: $email;
        $username = $local !== '' ? $local : $email;

        return new self($email, $username);
    }
}
