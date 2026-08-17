<?php

namespace DarkOak\Tests\Integration\Services;

use DarkOak\Models\AutoScalingRule;
use DarkOak\Models\AutoScalingHistory;
use DarkOak\Services\AutoScaling\AutoScalingService;
use DarkOak\Repositories\Wings\DaemonServerRepository;
use DarkOak\Tests\Integration\Api\Client\ClientApiIntegrationTestCase;

class AutoScalingServiceTest extends ClientApiIntegrationTestCase
{
    protected function tearDown(): void
    {
        AutoScalingHistory::query()->forceDelete();
        AutoScalingRule::query()->forceDelete();

        // Mockery installs error/exception handlers; PHPUnit 11.5 flags any test
        // that leaves them behind. Close BEFORE parent (matches EmailTemplateRendererTest).
        \Mockery::close();

        parent::tearDown();
    }

    private function makeServiceWithMock(DaemonServerRepository $mock): AutoScalingService
    {
        $this->app->instance(DaemonServerRepository::class, $mock);

        return new AutoScalingService();
    }

    public function testEvaluateScalingScalesUpWhenCpuExceedsThreshold(): void
    {
        // Mock the daemon repository so fetchServerStats + sync never touch the network.
        $mock = \Mockery::mock(DaemonServerRepository::class);
        $mock->shouldReceive('setServer')->andReturnSelf();
        $mock->shouldReceive('getDetails')->andReturn([
            'resources' => [
                'cpu_absolute' => 95,
                'memory_bytes' => 512 * 1024 * 1024,
                'memory_limit' => 1024 * 1024 * 1024,
                'disk_bytes' => 0,
                'disk_limit' => 10 * 1024 * 1024 * 1024,
            ],
        ]);
        $mock->shouldReceive('sync')->andReturnNull();

        $server = $this->createServerModel(['memory' => 1024]);
        AutoScalingRule::create([
            'server_id' => $server->id,
            'cpu_threshold' => 80,
            'memory_threshold' => 85,
            'disk_threshold' => 90,
            'scale_up_step' => 512,
            'scale_down_step' => 256,
            'min_memory' => 512,
            'max_memory' => 2048,
            'scale_up_cooldown' => 5,
            'scale_down_cooldown' => 10,
            'enabled' => true,
        ]);

        $service = $this->makeServiceWithMock($mock);
        $result = $service->evaluateScaling($server->fresh());

        $this->assertNotNull($result, 'Expected a scaling history entry when CPU is over threshold.');
        $this->assertSame('scale_up', $result->action);

        $server->refresh();
        $this->assertSame(1536, $server->memory, 'Memory should have increased by scale_up_step.');
    }

    public function testEvaluateScalingReturnsNullWhenRuleDisabled(): void
    {
        $mock = \Mockery::mock(DaemonServerRepository::class);
        $mock->shouldReceive('setServer', 'getDetails', 'sync');

        $server = $this->createServerModel(['memory' => 1024]);
        AutoScalingRule::create([
            'server_id' => $server->id,
            'cpu_threshold' => 80,
            'memory_threshold' => 85,
            'disk_threshold' => 90,
            'scale_up_step' => 512,
            'scale_down_step' => 256,
            'min_memory' => 512,
            'max_memory' => 2048,
            'scale_up_cooldown' => 5,
            'scale_down_cooldown' => 10,
            'enabled' => false,
        ]);

        $service = $this->makeServiceWithMock($mock);
        $this->assertNull($service->evaluateScaling($server->fresh()));
    }

    public function testCreateOrUpdateRulePersistsThresholds(): void
    {
        $mock = \Mockery::mock(DaemonServerRepository::class);
        $mock->shouldReceive('setServer', 'getDetails', 'sync');

        $server = $this->createServerModel(['memory' => 1024]);

        $service = $this->makeServiceWithMock($mock);
        $rule = $service->createOrUpdateRule($server->id, [
            'cpu_threshold' => 70,
            'enabled' => true,
        ]);

        $this->assertSame($server->id, $rule->server_id);
        $this->assertTrue($rule->enabled);
        $this->assertSame(70, $rule->cpu_threshold);

        // Defaults applied for unspecified fields.
        $this->assertSame(512, $rule->scale_up_step);
        $this->assertSame(8192, $rule->max_memory);
    }
}
