<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class DomainRoot extends Model
{
    public const RESOURCE_NAME = 'domain_root';

    protected $table = 'domain_roots';

    protected $fillable = [
        'name',
        'root_domain',
        'provider',
        'provider_config',
        'is_active',
    ];

    protected $casts = [
        'provider_config' => 'array',
        'is_active' => 'boolean',
    ];

    public static array $validationRules = [
        'name' => 'required|string|max:191',
        'root_domain' => 'required|string|max:191|unique:domain_roots,root_domain',
        'provider' => 'required|string|max:191',
        'provider_config' => 'nullable|array',
        'is_active' => 'boolean',
    ];

    public function domains(): HasMany
    {
        return $this->hasMany(ServerDomain::class, 'domain_root_id');
    }

    public function providerOption(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->provider_config ?? [], $key, $default);
    }

    public function usesAutomation(): bool
    {
        return $this->provider !== 'manual';
    }
}
