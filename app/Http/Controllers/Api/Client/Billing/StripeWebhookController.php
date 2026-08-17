<?php

namespace DarkOak\Http\Controllers\Api\Client\Billing;

use Stripe\Webhook;
use Stripe\StripeClient;
use DarkOak\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DarkOak\Models\Billing\Order;
use DarkOak\Exceptions\DisplayException;
use DarkOak\Services\Billing\CreateServerService;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;

/**
 * Stripe Webhook Controller.
 *
 * Handles incoming Stripe webhook events with signature verification.
 * CRITICAL: All webhook requests MUST be verified using the Stripe signature
 * to prevent payment fraud and unauthorized access.
 *
 * @security stripe-webhook-verification
 */
class StripeWebhookController extends ClientApiController
{
    private ?StripeClient $stripe = null;

    public function __construct(
        private CreateServerService $serverCreation,
        private SettingsRepositoryInterface $settings,
    ) {
        parent::__construct();

        $secret = $this->settings->get('settings::modules:billing:keys:secret');
        if (!empty($secret) && is_string($secret)) {
            $this->stripe = new StripeClient($secret);
        }
    }

    /**
     * Handle incoming Stripe webhook events.
     *
     * SECURITY CRITICAL: This method verifies the Stripe signature header
     * to ensure the webhook actually came from Stripe and not a malicious actor.
     * Without signature verification, attackers could forge payment events.
     *
     * @param Request $request The incoming HTTP request
     *
     * @return Response HTTP response (200 on success, 400/401 on verification failure)
     */
    public function handleWebhook(Request $request): Response
    {
        // Get the raw payload and Stripe signature header
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        // Get the webhook secret from configuration (config/cashier.php)
        $endpoint_secret = config('cashier.webhook.secret');

        // If no webhook secret is configured, we cannot verify signatures
        // In production, this should reject the request. In development, we might log a warning.
        if (empty($endpoint_secret)) {
            // Log security warning
            \Illuminate\Support\Facades\Log::warning('Stripe webhook received but STRIPE_WEBHOOK_SECRET is not configured', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // In production, reject unverified webhooks to prevent fraud
            if (app()->environment('production')) {
                return response('Webhook secret not configured', 500);
            }
        }

        $event = null;

        // Verify the webhook signature
        // This prevents attackers from sending fake payment events
        if (!empty($endpoint_secret)) {
            try {
                $event = Webhook::constructEvent(
                    $payload,
                    $sig_header,
                    $endpoint_secret
                );
            } catch (\UnexpectedValueException $e) {
                // Invalid payload
                \Illuminate\Support\Facades\Log::warning('Invalid Stripe webhook payload', [
                    'error' => $e->getMessage(),
                    'ip' => $request->ip(),
                ]);

                return response('Invalid payload', 400);
            } catch (\Stripe\Exception\SignatureVerificationException $e) {
                // Invalid signature - this could be an attack attempt
                \Illuminate\Support\Facades\Log::error('Invalid Stripe webhook signature', [
                    'error' => $e->getMessage(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response('Invalid signature', 401);
            }
        } else {
            // Fallback: Parse the event without verification (DEVELOPMENT ONLY)
            // In production, the above check would have already returned an error
            try {
                $event = json_decode($payload);
            } catch (\Exception $e) {
                return response('Invalid payload', 400);
            }
        }

        // Process the verified event
        return $this->processStripeEvent($event);
    }

    /**
     * Process a verified Stripe event.
     *
     * @param object $event The Stripe event object
     *
     * @return Response HTTP response
     */
    private function processStripeEvent(object $event): Response
    {
        // Handle different event types
        switch ($event->type) {
            case 'payment_intent.succeeded':
                return $this->handlePaymentIntentSucceeded($event->data->object);

            case 'payment_intent.payment_failed':
                return $this->handlePaymentIntentFailed($event->data->object);

            case 'payment_intent.canceled':
                return $this->handlePaymentIntentCanceled($event->data->object);

            case 'charge.refunded':
                return $this->handleChargeRefunded($event->data->object);

            case 'invoice.paid':
                return $this->handleInvoicePaid($event->data->object);

            case 'customer.subscription.created':
            case 'customer.subscription.updated':
                return $this->handleSubscriptionUpdated($event->data->object);

            case 'customer.subscription.deleted':
                return $this->handleSubscriptionDeleted($event->data->object);

            default:
                // Log unhandled events for debugging
                \Illuminate\Support\Facades\Log::info('Unhandled Stripe webhook event', [
                    'type' => $event->type,
                    'id' => $event->id ?? 'unknown',
                ]);

                return response('Event received', 200);
        }
    }

    /**
     * Handle successful payment intent.
     *
     * @param object $paymentIntent The payment intent object
     */
    private function handlePaymentIntentSucceeded(object $paymentIntent): Response
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();

        if (!$order) {
            \Illuminate\Support\Facades\Log::warning('Stripe webhook: Order not found for payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return response('Order not found', 200); // Return 200 to prevent Stripe retries
        }

        // Only update if order is still pending
        if ($order->status === Order::STATUS_PENDING) {
            // Capture the payment if it requires capture
            if ($this->stripe && $paymentIntent->status === 'requires_capture') {
                try {
                    $this->stripe->paymentIntents->capture($paymentIntent->id);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to capture payment', [
                        'order_id' => $order->id,
                        'payment_intent_id' => $paymentIntent->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response('Capture failed', 500);
                }
            }

            $order->update(['status' => Order::STATUS_PROCESSED]);

            \Illuminate\Support\Facades\Log::info('Order marked as processed via webhook', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }

        return response('Success', 200);
    }

    /**
     * Handle failed payment intent.
     *
     * @param object $paymentIntent The payment intent object
     */
    private function handlePaymentIntentFailed(object $paymentIntent): Response
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();

        if ($order && $order->status === Order::STATUS_PENDING) {
            $order->update(['status' => Order::STATUS_FAILED]);

            \Illuminate\Support\Facades\Log::info('Order marked as failed via webhook', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'failure_message' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
            ]);
        }

        return response('Success', 200);
    }

    /**
     * Handle canceled payment intent.
     *
     * @param object $paymentIntent The payment intent object
     */
    private function handlePaymentIntentCanceled(object $paymentIntent): Response
    {
        $order = Order::where('payment_intent_id', $paymentIntent->id)->first();

        if ($order && $order->status === Order::STATUS_PENDING) {
            $order->update(['status' => Order::STATUS_FAILED]);

            \Illuminate\Support\Facades\Log::info('Order marked as canceled via webhook', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        }

        return response('Success', 200);
    }

    /**
     * Handle refunded charge.
     *
     * @param object $charge The charge object
     */
    private function handleChargeRefunded(object $charge): Response
    {
        $order = Order::whereHas('paymentIntent', function ($query) use ($charge) {
            $query->where('id', $charge->payment_intent);
        })->first();

        if ($order) {
            \Illuminate\Support\Facades\Log::info('Charge refunded via webhook', [
                'order_id' => $order->id,
                'charge_id' => $charge->id,
                'refund_amount' => $charge->amount_refunded ?? 0,
            ]);
        }

        return response('Success', 200);
    }

    /**
     * Handle paid invoice (subscription payments).
     *
     * @param object $invoice The invoice object
     */
    private function handleInvoicePaid(object $invoice): Response
    {
        \Illuminate\Support\Facades\Log::info('Invoice paid via webhook', [
            'invoice_id' => $invoice->id,
            'customer' => $invoice->customer,
            'amount_paid' => $invoice->amount_paid,
        ]);

        return response('Success', 200);
    }

    /**
     * Handle subscription updates.
     *
     * @param object $subscription The subscription object
     */
    private function handleSubscriptionUpdated(object $subscription): Response
    {
        \Illuminate\Support\Facades\Log::info('Subscription updated via webhook', [
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
        ]);

        return response('Success', 200);
    }

    /**
     * Handle subscription deletion/cancellation.
     *
     * @param object $subscription The subscription object
     */
    private function handleSubscriptionDeleted(object $subscription): Response
    {
        \Illuminate\Support\Facades\Log::info('Subscription deleted via webhook', [
            'subscription_id' => $subscription->id,
        ]);

        // Find associated server and handle cancellation
        // This would be implemented based on your business logic

        return response('Success', 200);
    }

    /**
     * Get the Stripe client instance.
     *
     * @throws DisplayException
     */
    private function stripe(): StripeClient
    {
        if (!$this->stripe) {
            throw new DisplayException('Stripe API keys have not been configured.');
        }

        return $this->stripe;
    }
}
