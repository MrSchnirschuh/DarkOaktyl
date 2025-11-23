<?php

namespace DarkOak\Tests\Unit\Services\Billing;

use DarkOak\Models\Billing\BillingTerm;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Nest;
use DarkOak\Models\Egg;
use DarkOak\Models\Node;
use DarkOak\Services\Billing\BuilderOrderService;
use DarkOak\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class BuilderOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private BuilderOrderService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new BuilderOrderService();
    }

    public function testDeriveLimitsMapsConfiguredResources(): void
    {
        config()->set('modules.billing.builder.resource_map', [
            'memory' => ['attribute' => 'memory'],
            'disk' => ['attribute' => 'disk'],
        ]);

        config()->set('modules.billing.builder.defaults', [
            'memory' => 512,
            'disk' => 2048,
            'cpu' => 100,
        ]);

        $limits = $this->service->deriveLimits([
            'memory' => ['quantity' => 4096],
            'disk' => ['quantity' => 10240],
        ]);

        $this->assertSame([
            'memory' => 4096,
            'disk' => 10240,
            'cpu' => 100,
        ], $limits);
    }

    public function testBuildMetadataIncludesTermCouponsAndVariables(): void
    {
        $nest = Nest::factory()->create();
        $egg = Egg::factory()->create(['nest_id' => $nest->id]);

        $category = Category::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Java Servers',
            'description' => 'Test category',
            'icon' => null,
            'visible' => true,
            'nest_id' => $nest->id,
            'egg_id' => $egg->id,
        ]);

        $node = Node::factory()->create([
            'deployable' => true,
            'deployable_free' => false,
        ]);

        $term = BillingTerm::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Quarterly',
            'slug' => 'quarterly',
            'duration_days' => 90,
            'multiplier' => 3,
            'is_active' => true,
            'is_default' => false,
            'sort_order' => 1,
        ]);

        $quote = [
            'total' => 45.0,
            'resources' => [
                'memory' => [
                    'resource' => 'memory',
                    'display_name' => 'Memory',
                    'quantity' => 8192,
                    'unit' => 'MB',
                    'total' => 30.0,
                ],
            ],
        ];

        $metadata = $this->service->buildMetadata(
            $category,
            $node,
            ['memory' => 8192],
            $quote,
            ['memory' => 8192],
            [['key' => 'SERVER_JARFILE', 'value' => 'paper.jar']],
            $term,
            ['SAVE10'],
        );

        $this->assertSame($category->id, $metadata['category']['id']);
        $this->assertSame($node->id, $metadata['node']['id']);
        $this->assertSame('paper.jar', $metadata['variables'][0]['value']);
        $this->assertSame('SAVE10', $metadata['coupons'][0]);
        $this->assertSame($term->uuid, $metadata['term']['uuid']);
        $this->assertSame('USD', strtoupper($metadata['currency']['code']));
    }
}
