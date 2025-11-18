<?php

namespace DarkOak\Models;

use Illuminate\Database\Eloquent\Model;

class NodeSnapshot extends Model
{
    protected $table = 'node_snapshots';

    protected $fillable = [
        'node_id',
        'recorded_at',
        'cpu_percent',
        'memory_used_bytes',
        'memory_total_bytes',
        'disk_used_bytes',
        'disk_total_bytes',
        'disk_read_bytes',
        'disk_write_bytes',
        'network_rx_bytes',
        'network_tx_bytes',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'cpu_percent' => 'float',
        'memory_used_bytes' => 'integer',
        'memory_total_bytes' => 'integer',
        'disk_used_bytes' => 'integer',
        'disk_total_bytes' => 'integer',
        'disk_read_bytes' => 'integer',
        'disk_write_bytes' => 'integer',
        'network_rx_bytes' => 'integer',
        'network_tx_bytes' => 'integer',
    ];

    public $timestamps = true;
}
