<?php

namespace DarkOak\Tests\Unit\Services\Billing\ValueObjects;

use DarkOak\Services\Billing\ValueObjects\QuoteOptions;
use PHPUnit\Framework\TestCase;

final class QuoteOptionsTest extends TestCase
{
    public function test_defaults(): void
    {
        $options = QuoteOptions::fromArray([]);
        $this->assertTrue($options->snapToStep());
        $this->assertFalse($options->shouldValidateCapacity());
        $this->assertNull($options->node());
    }

    public function test_legacy_keys_are_respected(): void
    {
        $options = QuoteOptions::fromArray([
            'snap_to_step' => false,
            'validate_capacity' => true,
        ]);
        $this->assertFalse($options->snapToStep());
        $this->assertFalse($options->shouldValidateCapacity());
    }

    public function test_snap_to_step_accepts_string_yes(): void
    {
        $options = QuoteOptions::fromArray(['snapToStep' => 'yes']);
        $this->assertTrue($options->snapToStep());
    }

    public function test_to_array_contains_normalized_keys(): void
    {
        $options = QuoteOptions::fromArray(['extra' => 'value']);
        $array = $options->toArray();
        $this->assertArrayHasKey('snapToStep', $array);
        $this->assertArrayHasKey('validateCapacity', $array);
        $this->assertSame('value', $array['extra']);
    }
}
