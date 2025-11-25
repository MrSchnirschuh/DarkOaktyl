<?php

namespace DarkOak\Services\Billing;

use Carbon\Carbon;
use DarkOak\Models\Egg;
use Stripe\StripeObject;
use DarkOak\Models\Server;
use Illuminate\Http\Request;
use DarkOak\Models\Allocation;
use DarkOak\Models\EggVariable;
use DarkOak\Models\Billing\Order;
use DarkOak\Models\Billing\Product;
use DarkOak\Models\Billing\Category;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Models\Billing\BillingException;
use DarkOak\Services\Domains\DomainProvisioningService;
use DarkOak\Services\Servers\ServerCreationService;
use DarkOak\Services\Servers\VariableValidatorService;
use DarkOak\Exceptions\Service\Deployment\NoViableAllocationException;

class CreateServerService
{
    /**
     * CreateServerService constructor.
     */
    public function __construct(
        private ServerCreationService $creation,
        private VariableValidatorService $variableValidator,
        private DomainProvisioningService $domainProvisioning,
    ) {
    }

    /**
     * Process the creation of a server.
     */
    public function process(Request $request, Product $product, StripeObject $metadata, Order $order): Server
    {
        $egg = Egg::findOrFail($product->category->egg_id);

        $allocation = $this->getAllocation($metadata->node_id, $order->id);
        $environment = $this->getEnvironmentWithDefaults($egg->id);

        try {
            $server = $this->creation->handle([
                'node_id' => $metadata->node_id,
                'allocation_id' => $allocation,
                'egg_id' => $egg->id,
                'nest_id' => $product->category->nest_id,
                'name' => $request->user()->username . '\'s server',
                'owner_id' => $request->user()->id,
                'memory' => $product->memory_limit,
                'swap' => 0,
                'disk' => $product->disk_limit,
                'io' => 500,
                'cpu' => $product->cpu_limit,
                'startup' => $egg->startup,
                'environment' => $environment,
                'image' => current($egg->docker_images),
                'billing_product_id' => $product->id,
                'renewal_date' => Carbon::now()->addDays(30),
                'database_limit' => $product->database_limit,
                'backup_limit' => $product->backup_limit,
                'allocation_limit' => $product->allocation_limit,
                'subuser_limit' => 3,
            ]);
        } catch (DisplayException $ex) {
            BillingException::create([
                'order_id' => $order->id,
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'title' => 'Failed to create billable server',
                'description' => $ex->getMessage(),
            ]);

            throw new DisplayException('Unable to create server: ' . $ex->getMessage());
        }

        $this->triggerDomainProvisioning($server, $order);

        return $server;
    }

    /**
     * Process the creation of a server based on builder metadata.
     */
    public function processBuilder(Request $request, array $metadata, Order $order): Server
    {
        $categoryData = $metadata['category'] ?? null;
        $nodeData = $metadata['node'] ?? null;

        if (!is_array($categoryData) || empty($categoryData['id'])) {
            throw new DisplayException('Unable to determine category for this order.');
        }

        if (!is_array($nodeData) || empty($nodeData['id'])) {
            throw new DisplayException('Unable to determine node for this order.');
        }

        $category = Category::findOrFail((int) $categoryData['id']);
        $egg = Egg::findOrFail($category->egg_id);
        $nodeId = (int) $nodeData['id'];

        $limits = $metadata['limits'] ?? [];
        $defaults = config('modules.billing.builder.defaults', []);

        $memory = max(1, (int) ($limits['memory'] ?? ($defaults['memory'] ?? 1024)));
        $disk = max(1, (int) ($limits['disk'] ?? ($defaults['disk'] ?? 10240)));
        $cpu = max(1, (int) ($limits['cpu'] ?? ($defaults['cpu'] ?? 100)));
        $backupLimit = (int) ($limits['backup_limit'] ?? ($defaults['backup_limit'] ?? 0));
        $databaseLimit = (int) ($limits['database_limit'] ?? ($defaults['database_limit'] ?? 0));
        $allocationLimit = (int) ($limits['allocation_limit'] ?? ($defaults['allocation_limit'] ?? 1));
        $subuserLimit = (int) ($limits['subuser_limit'] ?? ($defaults['subuser_limit'] ?? 3));
        $swap = (int) ($limits['swap'] ?? ($defaults['swap'] ?? 0));
        $io = (int) ($limits['io'] ?? ($defaults['io'] ?? 500));

        $allocation = $this->getAllocation($nodeId, $order->id);
        $environment = $this->getEnvironmentWithDefaults($egg->id);
        $environment = $this->applyBuilderVariables($environment, $metadata['variables'] ?? []);

        $durationDays = (int) data_get($metadata, 'term.duration_days', 30);
        $durationDays = $durationDays > 0 ? $durationDays : 30;

        try {
            $server = $this->creation->handle([
                'node_id' => $nodeId,
                'allocation_id' => $allocation,
                'egg_id' => $egg->id,
                'nest_id' => $category->nest_id,
                'name' => $request->user()->username . '\'s server',
                'owner_id' => $request->user()->id,
                'memory' => $memory,
                'swap' => $swap,
                'disk' => $disk,
                'io' => $io,
                'cpu' => $cpu,
                'startup' => $egg->startup,
                'environment' => $environment,
                'image' => current($egg->docker_images),
                'billing_product_id' => null,
                'renewal_date' => Carbon::now()->addDays($durationDays),
                'database_limit' => $databaseLimit,
                'backup_limit' => $backupLimit,
                'allocation_limit' => $allocationLimit,
                'subuser_limit' => $subuserLimit,
            ]);
        } catch (DisplayException $ex) {
            BillingException::create([
                'order_id' => $order->id,
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'title' => 'Failed to create builder server',
                'description' => $ex->getMessage(),
            ]);

            throw new DisplayException('Unable to create server: ' . $ex->getMessage());
        }

        $this->triggerDomainProvisioning($server, $order);

        return $server;
    }

    /**
     * Process the creation of a free server.
     */
    public function processFree(Request $request, Product $product, int $nodeId, Order $order): Server
    {
        $egg = Egg::findOrFail($product->category->egg_id);

        $allocation = $this->getAllocation($nodeId, $order->id);
        $environment = $this->getEnvironmentWithDefaults($egg->id);

        try {
            $server = $this->creation->handle([
                'node_id' => $nodeId,
                'allocation_id' => $allocation,
                'egg_id' => $egg->id,
                'nest_id' => $product->category->nest_id,
                'name' => $request->user()->username . '\'s server',
                'owner_id' => $request->user()->id,
                'memory' => $product->memory_limit,
                'swap' => 0,
                'disk' => $product->disk_limit,
                'io' => 500,
                'cpu' => $product->cpu_limit,
                'startup' => $egg->startup,
                'environment' => $environment,
                'image' => current($egg->docker_images),
                'billing_product_id' => $product->id,
                'database_limit' => $product->database_limit,
                'backup_limit' => $product->backup_limit,
                'allocation_limit' => $product->allocation_limit,
                'subuser_limit' => 3,
            ]);
        } catch (DisplayException $ex) {
            BillingException::create([
                'order_id' => $order->id,
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'title' => 'Failed to create free server',
                'description' => $ex->getMessage(),
            ]);

            throw new DisplayException('Unable to create server: ' . $ex->getMessage());
        }

        $this->triggerDomainProvisioning($server, $order);

        return $server;
    }

    /**
     * Get all environment variables with their default values for an egg.
     */
    private function getEnvironmentWithDefaults(int $eggId): array
    {
        $variables = [];
        $defaults = EggVariable::where('egg_id', $eggId)->get();

        foreach ($defaults as $variable) {
            $variables[$variable->env_variable] = $variable->default_value;
        }

        return $variables;
    }

    private function applyBuilderVariables(array $environment, array $variables): array
    {
        foreach ($variables as $variable) {
            $key = $variable['key'] ?? null;
            if (!$key) {
                continue;
            }

            $environment[$key] = $variable['value'] ?? '';
        }

        return $environment;
    }

    /**
     * Get the environment variables for the new server.
     */
    private function getServerEnvironment(string $data, int $id): array
    {
        $decoded = json_decode($data, true);

        $variables = [];
        $default = EggVariable::where('egg_id', $id)->get();

        foreach ($decoded as $variable) {
            $variables += [$variable['key'] => $variable['value']];
        }

        foreach ($default as $variable) {
            if (!array_key_exists($variable->env_variable, $variables)) {
                $variables += [$variable->env_variable => $variable->default_value];
            }
        }

        return $variables;
    }

    /**
     * Get a suitable allocation to deploy to.
     */
    private function getAllocation(int $nodeId, int $orderId): int
    {
        $allocation = Allocation::where('node_id', $nodeId)->where('server_id', null)->first();

        if (!$allocation) {
            BillingException::create([
                'order_id' => $orderId,
                'exception_type' => BillingException::TYPE_DEPLOYMENT,
                'title' => 'Failed to find allocation to assign to server',
                'description' => 'Create more allocations (ports) for node ' . $nodeId,
            ]);

            throw new NoViableAllocationException('No allocations are available for deployment.');
        }

        return $allocation->id;
    }

    private function triggerDomainProvisioning(Server $server, Order $order): void
    {
        $request = $order->domain_request;

        if (empty($request)) {
            return;
        }

        $this->domainProvisioning->requestProvision($server, $request);
    }
}

