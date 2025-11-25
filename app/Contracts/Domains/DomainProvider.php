<?php

namespace DarkOak\Contracts\Domains;

use DarkOak\Models\DomainRoot;
use DarkOak\Models\ServerDomain;
use DarkOak\Services\Domains\DTO\DomainProvisioningResponse;

interface DomainProvider
{
    public function provision(ServerDomain $domain, DomainRoot $root): DomainProvisioningResponse;
}
