<?php

namespace DarkOak\Services\Billing\ValueObjects;

use DarkOak\Models\Node;
use Illuminate\Support\Arr;

final class QuoteOptions
{
    public const KEY_SNAP_TO_STEP = 'snapToStep';
    public const KEY_SNAP_TO_STEP_LEGACY = 'snap_to_step';
    public const KEY_VALIDATE_CAPACITY = 'validateCapacity';
    public const KEY_VALIDATE_CAPACITY_LEGACY = 'validate_capacity';

    private bool $snapToStep;
    private bool $validateCapacity;
    private ?Node $node;

    /**
     * @var array<string, mixed>
     */
    private array $raw;

    private function __construct(bool $snapToStep, bool $validateCapacity, ?Node $node, array $raw)
    {
        $this->snapToStep = $snapToStep;
        $this->validateCapacity = $validateCapacity;
        $this->node = $node;
        $this->raw = $raw;
    }

    /**
     * @param array<string, mixed> $options
     */
    public static function fromArray(array $options): self
    {
        $snapToStepValue = $options[self::KEY_SNAP_TO_STEP] ?? $options[self::KEY_SNAP_TO_STEP_LEGACY] ?? true;
        $snapToStep = filter_var($snapToStepValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $snapToStep = $snapToStep !== null ? $snapToStep : (bool) $snapToStepValue;

        $validateCapacityValue = $options[self::KEY_VALIDATE_CAPACITY] ?? $options[self::KEY_VALIDATE_CAPACITY_LEGACY] ?? null;
        $validateCapacity = $validateCapacityValue !== null
            ? filter_var($validateCapacityValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
            : null;
        $validateCapacity = $validateCapacity !== null ? $validateCapacity : false;

        $node = $options['node'] ?? null;
        $node = $node instanceof Node ? $node : null;

        $hasExplicitCapacityFlag = Arr::has($options, self::KEY_VALIDATE_CAPACITY)
            || Arr::has($options, self::KEY_VALIDATE_CAPACITY_LEGACY);

        if ($node && !$hasExplicitCapacityFlag) {
            $validateCapacity = true;
        }

        $raw = Arr::except($options, [
            'node',
            self::KEY_SNAP_TO_STEP,
            self::KEY_SNAP_TO_STEP_LEGACY,
            self::KEY_VALIDATE_CAPACITY,
            self::KEY_VALIDATE_CAPACITY_LEGACY,
        ]);
        $raw[self::KEY_SNAP_TO_STEP] = $snapToStep;
        $raw[self::KEY_VALIDATE_CAPACITY] = $validateCapacity;

        if ($node) {
            $raw['node_uuid'] = $node->uuid ?? null;
            $raw['node_id'] = $node->id ?? null;
        }

        return new self($snapToStep, $validateCapacity, $node, $raw);
    }

    public function snapToStep(): bool
    {
        return $this->snapToStep;
    }

    public function node(): ?Node
    {
        return $this->node;
    }

    public function shouldValidateCapacity(): bool
    {
        return $this->validateCapacity && $this->node !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }
}

