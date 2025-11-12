<?php

namespace Everest\Services\Billing\ValueObjects;

/**
 * @internal Value object representing a calculated billing quote.
 */
final class QuoteResult
{
    /**
     * @param array<string, ResourceSelection> $resources
     */
    public function __construct(
        private array $resources,
        private float $subtotal,
        private float $termMultiplier,
        private ?array $term,
        private QuoteOptions $options,
        private ?float $discount = null,
        private ?float $totalAfterDiscount = null,
    ) {
        $this->subtotal = round($subtotal, 4);
        $this->termMultiplier = max(0.0, $termMultiplier);
    }

    public function subtotal(): float
    {
        return $this->subtotal;
    }

    public function termMultiplier(): float
    {
        return $this->termMultiplier;
    }

    public function total(): float
    {
        return round($this->subtotal * $this->termMultiplier, 4);
    }

    public function options(): QuoteOptions
    {
        return $this->options;
    }

    /**
     * @return array<string, ResourceSelection>
     */
    public function resources(): array
    {
        return $this->resources;
    }

    public function term(): ?array
    {
        return $this->term;
    }

    public function discount(): ?float
    {
        return $this->discount;
    }

    public function totalAfterDiscount(): ?float
    {
        return $this->totalAfterDiscount;
    }

    public function withDiscount(float $discount): self
    {
        $discount = round($discount, 4);
        $totalAfterDiscount = max(0.0, round($this->total() - $discount, 4));

        return new self(
            $this->resources,
            $this->subtotal,
            $this->termMultiplier,
            $this->term,
            $this->options,
            $discount,
            $totalAfterDiscount,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $resources = [];
        foreach ($this->resources as $key => $selection) {
            $resources[$key] = $selection->toArray();
        }

        $data = [
            'subtotal' => $this->subtotal,
            'total' => $this->total(),
            'term_multiplier' => $this->termMultiplier,
            'term' => $this->term,
            'resources' => $resources,
            'options' => $this->options->toArray(),
        ];

        if ($this->discount !== null) {
            $data['discount'] = $this->discount;
            $data['total_after_discount'] = $this->totalAfterDiscount;
        }

        return $data;
    }
}
