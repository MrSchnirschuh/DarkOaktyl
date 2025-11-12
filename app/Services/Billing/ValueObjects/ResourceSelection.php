<?php

namespace Everest\Services\Billing\ValueObjects;

use Everest\Models\Billing\ResourcePrice;
use Everest\Models\Billing\ResourceScalingRule;
use Illuminate\Support\Arr;

final class ResourceSelection
{
    private const DEFAULT_MEMORY_RESOURCE = 'memory';
    private const DEFAULT_DISK_RESOURCE = 'disk';

    private ResourcePrice $resource;
    private int $requestedQuantity;
    private bool $snapToStep;
    private int $quantity;
    private float $total;

    /**
     * @var array<int, array{rule: string|null, mode: string, multiplier: float, threshold: int}>
     */
    private array $appliedRules = [];

    private function __construct(ResourcePrice $resource, int $requestedQuantity, bool $snapToStep)
    {
        $this->resource = $resource;
        $this->requestedQuantity = max(0, $requestedQuantity);
        $this->snapToStep = $snapToStep;

        $this->quantity = $this->normalizeQuantity($this->requestedQuantity);
        $this->calculateCost();
    }

    public static function fromResource(ResourcePrice $resource, int $requestedQuantity, QuoteOptions $options): self
    {
        return new self($resource, $requestedQuantity, $options->snapToStep());
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function total(): float
    {
        return $this->total;
    }

    /**
     * @return array<int, array{rule: string|null, mode: string, multiplier: float, threshold: int}>
     */
    public function appliedRules(): array
    {
        return $this->appliedRules;
    }

    /**
     * @return array<string, int>
     */
    public function capacityRequirements(): array
    {
        $metadata = $this->resource->metadata ?? [];
        $requirements = [];

        $configuredCapacity = $this->extractCapacityDefinitions($metadata);
        if (empty($configuredCapacity)) {
            $defaultMetric = $this->defaultCapacityMetric();
            if ($defaultMetric === null) {
                return [];
            }

            $configuredCapacity[] = ['metric' => $defaultMetric];
        }

        foreach ($configuredCapacity as $definition) {
            $metric = $definition['metric'] ?? null;
            if (!is_string($metric) || $metric === '') {
                continue;
            }

            $perUnit = $definition['per_unit'] ?? $definition['per_quantity'] ?? null;
            $perUnit = $perUnit !== null ? (float) $perUnit : 1.0;

            $value = (int) ceil($this->quantity * $perUnit);
            $value = max(0, $value);

            $requirements[$metric] = ($requirements[$metric] ?? 0) + $value;
        }

        return $requirements;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resource' => $this->resource->resource,
            'display_name' => $this->resource->display_name,
            'quantity' => $this->quantity,
            'unit' => $this->resource->unit,
            'base_quantity' => $this->resource->base_quantity,
            'base_price' => $this->resource->price,
            'applied_rules' => $this->appliedRules,
            'total' => $this->total,
        ];
    }

    private function normalizeQuantity(int $quantity): int
    {
        $quantity = max($quantity, (int) $this->resource->min_quantity);

        if ($this->resource->max_quantity !== null) {
            $quantity = min($quantity, (int) $this->resource->max_quantity);
        }

        $step = (int) $this->resource->step_quantity;
        if ($this->snapToStep && $step > 0) {
            $step = max(1, $step);
            $quantity = (int) (ceil($quantity / $step) * $step);
        }

        return $quantity;
    }

    private function calculateCost(): void
    {
        $quantity = $this->quantity;
        $baseQuantity = (int) $this->resource->base_quantity;
        $baseBlocks = $baseQuantity > 0 ? $quantity / $baseQuantity : $quantity;
        $baseCost = $baseBlocks * (float) $this->resource->price;

        $rules = $this->resource->scalingRules ?? collect();
        $multiplier = 1.0;
        $applied = [];

        foreach ($rules as $rule) {
            if (!$rule instanceof ResourceScalingRule) {
                continue;
            }

            if ($quantity < (int) $rule->threshold) {
                continue;
            }

            if ($rule->mode === 'multiplier') {
                $multiplier = (float) $rule->multiplier;
            } elseif ($rule->mode === 'surcharge') {
                $baseCost += (float) $rule->multiplier;
            }

            $applied[] = [
                'rule' => $rule->label,
                'mode' => $rule->mode,
                'multiplier' => (float) $rule->multiplier,
                'threshold' => (int) $rule->threshold,
            ];
        }

        $this->total = round($baseCost * $multiplier, 4);
        $this->appliedRules = $applied;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<int, array<string, mixed>>
     */
    private function extractCapacityDefinitions(array $metadata): array
    {
        $definitions = [];

        $configured = $metadata['node_capacity'] ?? null;
        if (is_array($configured)) {
            if (Arr::isAssoc($configured)) {
                $definitions[] = $configured;
            } else {
                foreach ($configured as $entry) {
                    if (is_array($entry)) {
                        $definitions[] = $entry;
                    }
                }
            }
        }

        if (isset($metadata['node_capacity_metric'])) {
            $definitions[] = [
                'metric' => $metadata['node_capacity_metric'],
                'per_unit' => $metadata['node_capacity_per_unit'] ?? 1,
            ];
        }

        return $definitions;
    }

    private function defaultCapacityMetric(): ?string
    {
        return match ($this->resource->resource) {
            self::DEFAULT_MEMORY_RESOURCE => 'memory_mb',
            self::DEFAULT_DISK_RESOURCE => 'disk_mb',
            default => null,
        };
    }
}
