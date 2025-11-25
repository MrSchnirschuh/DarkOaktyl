<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServerDomain extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROVISIONING = 'provisioning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ERROR = 'error';
    public const STATUS_DISABLED = 'disabled';

    protected $table = 'server_domains';

    protected $fillable = [
        'server_id',
        'domain_root_id',
        'type',
        'hostname',
        'subdomain',
        'status',
        'verification_method',
        'verification_token',
        'verified_at',
        'provider_payload',
        'last_synced_at',
    ];

    protected $casts = [
        'server_id' => 'int',
        'domain_root_id' => 'int',
        'verified_at' => 'datetime',
        'provider_payload' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(DomainRoot::class, 'domain_root_id');
    }
}
