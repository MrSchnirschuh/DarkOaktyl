<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Model;

class DomainRoot extends Model
{
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function isCloudflare(): bool
    {
        return $this->provider === 'cloudflare';
    }
}