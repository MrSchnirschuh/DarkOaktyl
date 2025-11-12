<?php

namespace DarkOak\Tests\Unit\Services\Billing;

use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Models\Billing\ResourcePrice;
use DarkOak\Models\Node;
use DarkOak\Services\Billing\BillingPricingService;
use DarkOak\Services\Servers\NodeCapacityService;
use DarkOak\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;

class BillingPricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BillingPricingService $service;

    private MockInterface $capacityService;

    public function setUp(): void
    {
        parent::setUp();

        $this->capacityService = Mockery::mock(NodeCapacityService::class);
        $this->service = new BillingPricingService($this->capacityService);
    }

    public function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testCalculatesQuoteAndValidatesCapacityWhenNodeProvided(): void
    {
        $memory = ResourcePrice::query()->create([
            'uuid' => (string) Str::uuid(),
            'resource' => 'memory',
            'display_name' => 'Memory',
            'description' => null,
            'unit' => 'MB',
            'base_quantity' => 1,
            'price' => 0.02,
            'currency' => 'USD',
            'min_quantity' => 512,
            'max_quantity' => null,
            'default_quantity' => 1024,
            'step_quantity' => 256,
            'is_visible' => true,
            'is_metered' => false,
            'sort_order' => 1,
            'metadata' => [
                'node_capacity' => [
                    'metric' => 'memory_mb',
                ],
            ],
        ]);

        $memory->scalingRules()->create([
            'threshold' => 2048,
            'multiplier' => 0.9,
            'mode' => 'multiplier',
            'label' => 'Bulk discount',
        ]);

        $disk = ResourcePrice::query()->create([
            'uuid' => (string) Str::uuid(),
            'resource' => 'disk',
            'display_name' => 'Disk',
            'description' => null,
            'unit' => 'MB',
            'base_quantity' => 1,
            'price' => 0.005,
            'currency' => 'USD',
            'min_quantity' => 10240,
            'max_quantity' => null,
            'default_quantity' => 20480,
            'step_quantity' => 1024,
            'is_visible' => true,
            'is_metered' => false,
            'sort_order' => 2,
            'metadata' => [
                'node_capacity' => [
                    'metric' => 'disk_mb',
                ],
            ],
        ]);

        $term = BillingTerm::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Half Off',
            'slug' => 'half-off',
            'duration_days' => 30,
            'multiplier' => 0.5,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 1,
        ]);

        $node = Node::factory()->create([
            'memory' => 16384,
            'disk' => 32768,
        ]);

        $this->capacityService
            ->shouldReceive('assertCanAllocate')
            ->once()
            ->with($node, 2304, 20480);

        $quote = $this->service->calculateQuote([
            'memory' => 2200,
            'disk' => 20480,
        ], $term, [
            'snapToStep' => true,
            'node' => $node,
        ]);

        $this->assertSame(143.872, $quote['subtotal']);
        $this->assertSame(71.936, $quote['total']);
        $this->assertSame(0.5, $quote['term_multiplier']);
        $this->assertTrue($quote['options']['snapToStep']);
        $this->assertTrue($quote['options']['validateCapacity']);
        $this->assertEquals(2304, $quote['resources']['memory']['quantity']);
        $this->assertEquals(41.472, $quote['resources']['memory']['total']);
        $this->assertEquals(102.4, $quote['resources']['disk']['total']);
    }

    public function testSkipsCapacityValidationWhenDisabled(): void
    {
        $resource = ResourcePrice::query()->create([
            'uuid' => (string) Str::uuid(),
            'resource' => 'memory',
            'display_name' => 'Memory',
            'description' => null,
            'unit' => 'MB',
            'base_quantity' => 1,
            'price' => 0.02,
            'currency' => 'USD',
            'min_quantity' => 512,
            'max_quantity' => null,
            'default_quantity' => 1024,
            'step_quantity' => 256,
            'is_visible' => true,
            'is_metered' => false,
            'sort_order' => 1,
            'metadata' => [
                'node_capacity' => [
                    'metric' => 'memory_mb',
                ],
            ],
        ]);

        $node = Node::factory()->create([
            'memory' => 16384,
            'disk' => 32768,
        ]);

        $this->capacityService->shouldReceive('assertCanAllocate')->never();

        $quote = $this->service->calculateQuote([
            'memory' => 1024,
        ], null, [
            'snapToStep' => true,
            'node' => $node,
            'validate_capacity' => false,
        ]);

        $this->assertSame(20.48, $quote['subtotal']);
        $this->assertFalse($quote['options']['validateCapacity']);
    }
}

