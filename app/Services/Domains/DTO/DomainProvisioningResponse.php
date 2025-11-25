<?php

namespace DarkOak\Services\Domains\DTO;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class DomainProvisioningResponse
{
    public function __construct(
        public array $providerPayload = [],
        public bool $verified = false,
        public ?CarbonInterface $verifiedAt = null,
        public ?CarbonInterface $syncedAt = null
    ) {
        if ($this->verified && is_null($this->verifiedAt)) {
            $this->verifiedAt = CarbonImmutable::now();
        }

        if (is_null($this->syncedAt)) {
            $this->syncedAt = CarbonImmutable::now();
        }
    }
}
