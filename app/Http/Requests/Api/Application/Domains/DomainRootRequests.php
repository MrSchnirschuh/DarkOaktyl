<?php

namespace DarkOak\Http\Requests\Api\Application\Domains;

use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreDomainRootRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'root_domain' => 'required|string|max:255',
            'provider' => 'required|string|in:manual,cloudflare',
            'provider_config' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}

class UpdateDomainRootRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'root_domain' => 'sometimes|string|max:255',
            'provider' => 'sometimes|string|in:manual,cloudflare',
            'provider_config' => 'nullable|array',
            'is_active' => 'boolean',
        ];
    }
}

class DeleteDomainRootRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [];
    }
}

class GetDomainRootsRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return [];
    }
}