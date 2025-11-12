<?php

namespace DarkOak\Tests\Unit\Services\Servers;

use DarkOak\Models\Node;
use Mockery\MockInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use DarkOak\Services\Servers\NodeCapacityService;
use DarkOak\Repositories\Wings\DaemonConfigurationRepository;
use DarkOak\Exceptions\Service\Deployment\NoViableNodeException;
use DarkOak\Exceptions\Http\Connection\DaemonConnectionException;
use DarkOak\Tests\TestCase;
use GuzzleHttp\Exception\GuzzleException;

class NodeCapacityServiceTest extends TestCase
{
    use RefreshDatabase;

    private NodeCapacityService $service;

    private MockInterface $configurationRepository;

    public function setUp(): void
    {
        parent::setUp();

        config()->set('modules.billing.enabled', true);

        $this->configurationRepository = \Mockery::mock(DaemonConfigurationRepository::class);
        $this->service = new NodeCapacityService($this->configurationRepository);
    }

    public function testThrowsWhenNodeNotViable(): void
    {
        $node = Node::factory()->create([
            'memory' => 256,
            'disk' => 256,
        ]);

        $this->configurationRepository->shouldReceive('setNode')->never();

        $this->expectException(NoViableNodeException::class);

        $this->service->assertCanAllocate($node, 512, 10);
    }

    public function testThrowsWhenSuperchargedReportsInsufficientCapacity(): void
    {
        $node = Node::factory()->create([
            'memory' => 4096,
            'disk' => 4096,
        ]);

        $this->configurationRepository->shouldReceive('setNode')->with($node)->twice()->andReturnSelf();
        $this->configurationRepository->shouldReceive('getSystemInformation')->once()->andReturn([
            'system' => ['supercharged' => true],
        ]);
        $this->configurationRepository->shouldReceive('getSystemUtilization')->once()->andReturn([
            'memory_total' => 1024 * 1024 * 1024,
            'memory_used' => 900 * 1024 * 1024,
            'disk_total' => 1024 * 1024 * 1024,
            'disk_used' => 900 * 1024 * 1024,
        ]);

        $this->expectException(NoViableNodeException::class);

        $this->service->assertCanAllocate($node, 256, 256);
    }

    public function testSkipsDaemonCapacityWhenNotSupercharged(): void
    {
        $node = Node::factory()->create([
            'memory' => 4096,
            'disk' => 4096,
        ]);

        $this->configurationRepository->shouldReceive('setNode')->with($node)->once()->andReturnSelf();
        $this->configurationRepository->shouldReceive('getSystemInformation')->once()->andReturn([
            'system' => ['supercharged' => false],
        ]);
        $this->configurationRepository->shouldReceive('getSystemUtilization')->never();

        $this->service->assertCanAllocate($node, 256, 256);

        $this->assertTrue(true);
    }

    public function testFallsBackWhenDaemonUnavailable(): void
    {
        $node = Node::factory()->create([
            'memory' => 4096,
            'disk' => 4096,
        ]);

        $this->configurationRepository->shouldReceive('setNode')->with($node)->once()->andReturnSelf();
        $this->configurationRepository->shouldReceive('getSystemInformation')
            ->once()
            ->andThrow(new DaemonConnectionException(\Mockery::mock(GuzzleException::class)));

        $this->service->assertCanAllocate($node, 256, 256);

        $this->assertTrue(true);
    }

    public function testPassesWhenCapacityAvailable(): void
    {
        $node = Node::factory()->create([
            'memory' => 4096,
            'disk' => 4096,
        ]);

        $this->configurationRepository->shouldReceive('setNode')->with($node)->twice()->andReturnSelf();
        $this->configurationRepository->shouldReceive('getSystemInformation')->once()->andReturn([
            'system' => ['supercharged' => true],
        ]);
        $this->configurationRepository->shouldReceive('getSystemUtilization')->once()->andReturn([
            'memory_total' => 4 * 1024 * 1024 * 1024,
            'memory_used' => 1024 * 1024 * 1024,
            'disk_total' => 4 * 1024 * 1024 * 1024,
            'disk_used' => 1024 * 1024 * 1024,
        ]);

        $this->service->assertCanAllocate($node, 512, 512);

        $this->assertTrue(true);
    }
}

