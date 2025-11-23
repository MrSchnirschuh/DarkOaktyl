<?php

namespace DarkOak\Tests\Unit\Services\Billing;

use Carbon\Carbon;
use DarkOak\Models\Allocation;
use DarkOak\Models\Billing\Category;
use DarkOak\Models\Billing\Order;
use DarkOak\Models\Egg;
use DarkOak\Models\Nest;
use DarkOak\Models\Node;
use DarkOak\Models\Server;
use DarkOak\Models\User;
use DarkOak\Services\Billing\CreateServerService;
use DarkOak\Services\Servers\ServerCreationService;
use DarkOak\Services\Servers\VariableValidatorService;
use DarkOak\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mockery;
use Mockery\MockInterface;

class CreateServerServiceTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $creationService;

    private MockInterface $variableValidator;

    private CreateServerService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->creationService = Mockery::mock(ServerCreationService::class);
        $this->variableValidator = Mockery::mock(VariableValidatorService::class);
        $this->service = new CreateServerService($this->creationService, $this->variableValidator);
    }

    public function tearDown(): void
    {
        Mockery::close();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testProcessBuilderUsesBuilderLimitsAndTermDuration(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 1, 1));

        $user = User::factory()->create(['username' => 'tester']);
        $nest = Nest::factory()->create();
        $egg = Egg::factory()->create(['nest_id' => $nest->id]);

        $category = Category::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => 'Custom Plan',
            'description' => null,
            'icon' => null,
            'visible' => true,
            'nest_id' => $nest->id,
            'egg_id' => $egg->id,
        ]);

        $node = Node::factory()->create([
            'deployable' => true,
            'deployable_free' => true,
        ]);

        Allocation::factory()->create([
            'node_id' => $node->id,
            'server_id' => null,
        ]);

        $order = Order::query()->create([
            'name' => 'builder-order',
            'user_id' => $user->id,
            'description' => 'Builder storefront order',
            'total' => 25.00,
            'status' => 'pending',
            'product_id' => null,
            'is_renewal' => false,
            'payment_intent_id' => 'pi_test',
            'storefront' => 'builder',
        ]);

        $metadata = [
            'category' => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'nest_id' => $category->nest_id,
                'egg_id' => $category->egg_id,
            ],
            'node' => [
                'id' => $node->id,
                'name' => $node->name,
                'fqdn' => $node->fqdn,
                'type' => 'paid',
            ],
            'limits' => [
                'memory' => 4096,
                'disk' => 20480,
                'cpu' => 150,
                'backup_limit' => 2,
                'database_limit' => 1,
                'allocation_limit' => 2,
                'subuser_limit' => 5,
                'swap' => 256,
                'io' => 600,
            ],
            'variables' => [
                ['key' => 'SERVER_JARFILE', 'value' => 'paper.jar'],
            ],
            'term' => [
                'duration_days' => 45,
            ],
        ];

        $expectedServer = Server::factory()->make();

        $this->creationService
            ->shouldReceive('handle')
            ->once()
            ->with(Mockery::on(function (array $payload) use ($node, $egg) {
                $this->assertSame($node->id, $payload['node_id']);
                $this->assertSame(4096, $payload['memory']);
                $this->assertSame(20480, $payload['disk']);
                $this->assertSame(150, $payload['cpu']);
                $this->assertSame($egg->id, $payload['egg_id']);
                $this->assertSame('tester\'s server', $payload['name']);
                $this->assertSame(2, $payload['backup_limit']);
                $this->assertSame(1, $payload['database_limit']);
                $this->assertEquals(Carbon::now()->addDays(45), $payload['renewal_date']);
                $this->assertSame('paper.jar', $payload['environment']['SERVER_JARFILE']);

                return true;
            }))
            ->andReturn($expectedServer);

        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);

        $server = $this->service->processBuilder($request, $metadata, $order);

        $this->assertSame($expectedServer, $server);
    }
}
