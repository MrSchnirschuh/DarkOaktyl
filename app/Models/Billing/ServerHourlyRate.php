<?php

namespace DarkOak\Models\Billing;

use Carbon\Carbon;
use DarkOak\Models\Model;
use DarkOak\Models\Server;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $server_id
 * @property float $hourly_rate
 * @property float|null $memory_rate
 * @property float|null $cpu_rate
 * @property float|null $disk_rate
 * @property array|null $pricing_breakdown
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Server $server
 */
class ServerHourlyRate extends Model
{
    public const RESOURCE_NAME = 'server_hourly_rate';

    protected $table = 'server_hourly_rates';

    protected $fillable = [
        'server_id',
        'hourly_rate',
        'memory_rate',
        'cpu_rate',
        'disk_rate',
        'pricing_breakdown',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'hourly_rate' => 'float',
        'memory_rate' => 'float',
        'cpu_rate' => 'float',
        'disk_rate' => 'float',
        'pricing_breakdown' => 'array',
    ];

    public static array $validationRules = [
        'server_id' => 'required|integer|exists:servers,id',
        'hourly_rate' => 'required|numeric|min:0',
        'memory_rate' => 'nullable|numeric|min:0',
        'cpu_rate' => 'nullable|numeric|min:0',
        'disk_rate' => 'nullable|numeric|min:0',
        'pricing_breakdown' => 'nullable|array',
    ];

    /**
     * Get the server associated with this rate.
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Calculate the hourly rate based on server resources.
     */
    public static function calculateRate(Server $server, ?Product $product = null): float
    {
        if ($product) {
            // Convert monthly price to hourly (approximate: 730 hours/month)
            $baseRate = $product->price / 730;
        } else {
            // Calculate based on resources
            $memoryRate = config('billing.rates.memory_per_mb', 0.0001);
            $cpuRate = config('billing.rates.cpu_per_percent', 0.001);
            $diskRate = config('billing.rates.disk_per_mb', 0.00005);

            $baseRate = ($server->memory * $memoryRate) +
                       ($server->cpu * $cpuRate) +
                       ($server->disk * $diskRate);
        }

        return round($baseRate, 4);
    }

    /**
     * Get or create rate for a server.
     */
    public static function forServer(int $serverId, ?float $hourlyRate = null): self
    {
        $rate = self::firstOrNew(['server_id' => $serverId]);

        if (!$rate->exists && $hourlyRate !== null) {
            $rate->hourly_rate = $hourlyRate;
            $rate->save();
        } elseif (!$rate->exists) {
            // Get server and calculate default rate
            $server = Server::find($serverId);
            if ($server) {
                $rate->hourly_rate = self::calculateRate($server);
                $rate->save();
            }
        }

        return $rate;
    }

    /**
     * Calculate cost for given hours.
     */
    public function calculateCost(float $hours): float
    {
        return round($hours * $this->hourly_rate, 4);
    }
}
