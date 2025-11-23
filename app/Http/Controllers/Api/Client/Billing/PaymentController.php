<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use DarkOak\Models\Node;
use Stripe\StripeClient;
use DarkOak\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DarkOak\Models\Billing\Order;
use Illuminate\Http\JsonResponse;
use DarkOak\Models\Billing\Product;
use DarkOak\Models\Billing\Category;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Models\Billing\BillingException;
use DarkOak\Services\Billing\CreateOrderService;
use DarkOak\Services\Billing\CreateServerService;
use DarkOak\Services\Billing\BuilderQuoteService;
use DarkOak\Services\Billing\BuilderOrderService;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Http\Requests\Api\Client\Billing\Builder\BuilderIntentRequest;
use DarkOak\Http\Requests\Api\Client\Billing\Builder\BuilderIntentUpdateRequest;

class PaymentController extends ClientApiController
{
    private ?StripeClient $stripe = null;

    public function __construct(
        private CreateOrderService $orderService,
        private CreateServerService $serverCreation,
        private SettingsRepositoryInterface $settings,
        private BuilderQuoteService $builderQuotes,
        private BuilderOrderService $builderOrders,
    ) {
        parent::__construct();

        $secret = $this->settings->get('settings::modules:billing:keys:secret');
        if (!empty($secret) && is_string($secret)) {
            $this->stripe = new StripeClient($secret);
        }
    }

    /**
     * Send the Stripe public key to the frontend.
     */
    public function publicKey(Request $request, int $id): JsonResponse
    {
        $publicKey = $this->settings->get('settings::modules:billing:keys:publishable') ?? null;

        if (!$publicKey) {
            BillingException::create([
                'exception_type' => BillingException::TYPE_STOREFRONT,
                'title' => 'The Stripe Public API key is missing',
                'description' => 'Add the Stripe \'publishable\' key to your billing panel',
            ]);
        }

        return response()->json([
            'key' => $this->settings->get('settings::modules:billing:keys:publishable'),
        ]);
    }

    public function builderKey(Request $request): JsonResponse
    {
        return $this->publicKey($request, 0);
    }

    /**
     * Create a Stripe payment intent.
     */
    public function intent(Request $request, int $id): JsonResponse
    {
        $stripe = $this->stripe();
        $paymentMethodTypes = ['card'];
        $product = Product::findOrFail($id);

        if ($this->settings->get('settings::modules:billing:paypal')) {
            $paymentMethodTypes[] = 'paypal';
        }

        if ($this->settings->get('settings::modules:billing:link')) {
            $paymentMethodTypes[] = 'link';
        }

        // Create payment intent with manual capture
    $paymentIntent = $stripe->paymentIntents->create([
            'amount' => $product->price * 100,
            'currency' => strtolower(config('modules.billing.currency.code')),
            'payment_method_types' => array_values($paymentMethodTypes),
            'capture_method' => 'manual', // Prevent immediate capture
        ]);

        if (!$paymentIntent->client_secret) {
            BillingException::create([
                'exception_type' => BillingException::TYPE_STOREFRONT,
                'title' => 'The PaymentIntent client secret was not generated',
                'description' => 'Double check your billing API keys and Stripe Dashboard',
            ]);
        }

        return response()->json([
            'id' => $paymentIntent->id,
            'secret' => $paymentIntent->client_secret,
        ]);
    }

    public function builderIntent(BuilderIntentRequest $request): JsonResponse
    {
        $stripe = $this->stripe();

        if (config('modules.billing.enabled') !== '1') {
            throw new DisplayException('The billing module is not enabled.');
        }

        $category = Category::findOrFail($request->input('category_id'));
        $node = Node::findOrFail($request->input('node_id'));

        $quoteResult = $this->builderQuotes->calculateFromRequest($request);
        $limits = $this->builderOrders->deriveLimits($quoteResult['quote']['resources'] ?? []);
        $variables = $request->input('variables', []);
        $couponCodes = $request->input('coupons', []);

        $total = $this->builderOrders->resolveTotal($quoteResult['quote']);
        if ($total <= 0.0) {
            throw new DisplayException('This configuration does not require payment. Use the free checkout.');
        }

        $this->assertNodeType($node, false);

        $paymentMethodTypes = ['card'];
        if ($this->settings->get('settings::modules:billing:paypal')) {
            $paymentMethodTypes[] = 'paypal';
        }

        if ($this->settings->get('settings::modules:billing:link')) {
            $paymentMethodTypes[] = 'link';
        }

        $paymentIntent = $stripe->paymentIntents->create([
            'amount' => (int) round($total * 100),
            'currency' => strtolower(config('modules.billing.currency.code')),
            'payment_method_types' => array_values($paymentMethodTypes),
            'capture_method' => 'manual',
            'metadata' => [
                'customer_email' => $request->user()->email,
                'customer_name' => $request->user()->username,
                'storefront' => Order::STOREFRONT_BUILDER,
                'node_id' => (string) $node->id,
                'server_id' => (string) ($request->input('server_id') ?? 0),
                'variables' => !empty($variables) ? json_encode($variables) : '',
            ],
        ]);

        if (!$paymentIntent->client_secret) {
            BillingException::create([
                'exception_type' => BillingException::TYPE_STOREFRONT,
                'title' => 'The PaymentIntent client secret was not generated',
                'description' => 'Double check your billing API keys and Stripe Dashboard',
            ]);
        }

        $metadata = $this->builderOrders->buildMetadata(
            $category,
            $node,
            $quoteResult['selections'],
            $quoteResult['quote'],
            $limits,
            $variables,
            $quoteResult['term'],
            $couponCodes,
        );

        $this->orderService->create(
            $paymentIntent->id,
            $request->user(),
            null,
            Order::STATUS_PENDING,
            $this->getOrderType($request),
            [
                'description' => substr($category->name, 0, 32) . ' builder order for ' . $request->user()->email,
                'total' => $total,
                'storefront' => Order::STOREFRONT_BUILDER,
                'billing_term_id' => $quoteResult['term']?->id,
                'node_id' => $node->id,
                'builder_metadata' => $metadata,
            ],
        );

        return response()->json([
            'id' => $paymentIntent->id,
            'secret' => $paymentIntent->client_secret,
        ]);
    }

    public function updateBuilderIntent(BuilderIntentUpdateRequest $request): Response
    {
        $stripe = $this->stripe();
        $intent = $stripe->paymentIntents->retrieve($request->input('intent'));

        if (!$intent) {
            BillingException::create([
                'exception_type' => BillingException::TYPE_STOREFRONT,
                'title' => 'The PaymentIntent requested does not exist',
                'description' => 'Check Stripe Dashboard and ask in the DarkOaktyl Discord for support',
            ]);
        }

        $order = Order::where('payment_intent_id', $request->input('intent'))
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->storefront !== Order::STOREFRONT_BUILDER) {
            throw new DisplayException('This order is not associated with the builder storefront.');
        }

        $node = Node::findOrFail($request->input('node_id'));
        $this->assertNodeType($node, false);

        $variables = $request->input('variables', []);

        $intent->metadata = array_merge($intent->metadata ?? [], [
            'customer_email' => $request->user()->email,
            'customer_name' => $request->user()->username,
            'storefront' => Order::STOREFRONT_BUILDER,
            'node_id' => (string) $node->id,
            'server_id' => (string) ($request->input('server_id') ?? 0),
            'variables' => !empty($variables) ? json_encode($variables) : '',
        ]);
        $intent->save();

        $metadata = $order->builder_metadata ?? [];
        $metadata['node'] = [
            'id' => $node->id,
            'name' => $node->name,
            'fqdn' => $node->fqdn,
            'type' => $node->deployable_free ? 'free' : 'paid',
        ];
        $metadata['variables'] = $variables;

        $order->builder_metadata = $metadata;
        $order->node_id = $node->id;
        $order->type = $this->getOrderType($request);
        $order->save();

        return $this->returnNoContent();
    }

    /**
     * Update a Payment Intent with new data from the UI.
     */
    public function updateIntent(Request $request, ?int $id = null): Response
    {
        $stripe = $this->stripe();
        $product = Product::findOrFail($id);
        $intent = $stripe->paymentIntents->retrieve($request->input('intent'));

        if (config('modules.billing.enabled') !== '1') {
            throw new DisplayException('The billing module is not enabled.');
        }

        if (!Node::findOrFail($request->input('node_id'))->deployable) {
            throw new DisplayException('Paid servers cannot be deployed to this node.');
        }

        if (!$intent) {
            BillingException::create([
                'exception_type' => BillingException::TYPE_STOREFRONT,
                'title' => 'The PaymentIntent requested does not exist',
                'description' => 'Check Stripe Dashboard and ask in the DarkOaktyl Discord for support',
            ]);
        }

        $metadata = [
            'customer_email' => $request->user()->email,
            'customer_name' => $request->user()->username,
            'product_id' => (string) $id,
            'node_id' => (string) ($request->input('node_id') ?? ''),
            'server_id' => (string) ($request->input('server_id') ?? 0),
        ];

        $variables = $request->input('variables') ?? [];
        $metadata['variables'] = !empty($variables) ? json_encode($variables) : '';

        $intent->metadata = $metadata;
        $intent->save();

        // Create the order
        $this->orderService->create(
            $intent->id,
            $request->user(),
            $product,
            Order::STATUS_PENDING,
            $this->getOrderType($request),
            [
                'storefront' => Order::STOREFRONT_PRODUCTS,
            ],
        );

        return $this->returnNoContent();
    }

    /**
     * Process a successful subscription purchase.
     */
    public function process(Request $request): Response
    {
        $stripe = $this->stripe();
        $intent = $stripe->paymentIntents->retrieve($request->input('intent'));

        $order = Order::where('payment_intent_id', $request->input('intent'))
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$this->settings->get('settings::modules:billing:enabled') && !$intent) {
            throw new DisplayException('Unable to fetch payment intent from Stripe.');
        }

        // Check if order has already been processed
        if (
            $order->status === Order::STATUS_PROCESSED
            && $intent->id === $order->payment_intent_id
        ) {
            throw new DisplayException('This order has already been processed.');
        }

        // If the payment wasn't successful, mark the order as failed
        if ($intent->status !== 'requires_capture') {
            $order->update(['status' => Order::STATUS_FAILED]);
            throw new DisplayException('The order has been canceled.');
        }

        // Process the renewal or product purchase
        if ($order->storefront === Order::STOREFRONT_BUILDER) {
            $builderMetadata = $order->builder_metadata ?? [];

            if (!empty($intent->metadata->variables)) {
                $builderMetadata['variables'] = json_decode($intent->metadata->variables, true) ?? ($builderMetadata['variables'] ?? []);
            }

            if (!empty($intent->metadata->node_id)) {
                $node = Node::find((int) $intent->metadata->node_id);
                if ($node) {
                    $builderMetadata['node'] = [
                        'id' => $node->id,
                        'name' => $node->name,
                        'fqdn' => $node->fqdn,
                        'type' => $node->deployable_free ? 'free' : 'paid',
                    ];
                }
            }

            if ($order->type === Order::TYPE_REN && ((int) $intent->metadata->server_id != 0)) {
                $server = Server::findOrFail((int) $intent->metadata->server_id);

                $server->update([
                    'renewal_date' => $server->renewal_date->addDays(30),
                    'status' => $server->isSuspended() ? null : $server->status,
                ]);
            } else {
                $server = $this->serverCreation->processBuilder($request, $builderMetadata, $order);
            }
        } elseif ($order->type === Order::TYPE_REN && ((int) $intent->metadata->server_id != 0)) {
            $server = Server::findOrFail((int) $intent->metadata->server_id);

            $server->update([
                'renewal_date' => $server->renewal_date->addDays(30),
                'status' => $server->isSuspended() ? null : $server->status,
            ]);
        } else {
            $product = Product::findOrFail($intent->metadata->product_id);

            $metadata = $intent->metadata;
            if (!empty($metadata->variables)) {
                $metadata->variables = json_decode($metadata->variables, true) ?? [];
            }

            $server = $this->serverCreation->process($request, $product, $metadata, $order);
        }

        // Capture the payment after processing the order
        if ($intent->status === 'requires_capture') {
            try {
                $intent->capture(); // Capture the payment now that the order is processed
            } catch (DisplayException $ex) {
                $server->delete();

                BillingException::create([
                    'order_id' => $order->id,
                    'exception_type' => BillingException::TYPE_PAYMENT,
                    'title' => 'Failed to capture payment via Stripe',
                    'description' => 'Check Stripe Dashboard and ask in the DarkOaktyl Discord for support',
                ]);

                throw new DisplayException('Unable to capture payment for this order.');
            }
        }

        // Mark the order as processed
        $order->update([
            'status' => Order::STATUS_PROCESSED,
            'name' => $order->name . substr($server->uuid, 0, 8),
        ]);

        return $this->returnNoContent();
    }

    /**
     * Determine whether an order is a NEW, UPGRADE or RENEWAL.
     */
    private function getOrderType(Request $request): mixed
    {
        $type = null;

        if ($request->has('renewal') && $request->boolean('renewal')) {
            $type = Order::TYPE_REN;
        } else {
            $type = Order::TYPE_NEW;
        }

        return $type;
    }

    private function stripe(): StripeClient
    {
        if (!$this->stripe) {
            throw new DisplayException('Stripe API keys have not been configured.');
        }

        return $this->stripe;
    }

    private function assertNodeType(Node $node, bool $isFree): void
    {
        if ($isFree && !$node->deployable_free) {
            throw new DisplayException('Free servers cannot be deployed to this node.');
        }

        if (!$isFree && !$node->deployable) {
            throw new DisplayException('Paid servers cannot be deployed to this node.');
        }
    }
}


