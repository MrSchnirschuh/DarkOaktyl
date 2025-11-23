<?php

namespace DarkOak\Services\Billing;

use DarkOak\Models\User;
use DarkOak\Models\Billing\Order;
use DarkOak\Models\Billing\Product;
use Illuminate\Support\Arr;

class CreateOrderService
{
    /**
     * Create or update an order associated with the provided payment intent.
     */
    public function create(
        ?string $intent,
        User $user,
        ?Product $product,
        ?string $status,
        ?string $type,
        array $attributes = []
    ): Order {
        $paymentIntent = $intent ?? Arr::get($attributes, 'payment_intent_id');
        $paymentIntent ??= 'free-' . substr(uuid_create(), 0, 16);

        $order = Order::firstOrNew(['payment_intent_id' => $paymentIntent]);
        $order->name = $order->name ?? Arr::get($attributes, 'name', uuid_create());
        $order->user_id = $user->id;

        $defaultDescriptionTarget = $product?->name ?? 'custom server';
        $defaultDescription = substr($order->name, 0, 8) . ' - Order for ' . $defaultDescriptionTarget . ' by ' . $user->email;
        $order->description = Arr::get($attributes, 'description', $defaultDescription);

        $order->total = Arr::get($attributes, 'total', $product?->price ?? 0);
        $order->status = $status ?? Order::STATUS_EXPIRED;
        $order->product_id = $product?->id;
        $order->type = $type ?? Order::TYPE_NEW;
        $order->storefront = Arr::get($attributes, 'storefront', Order::STOREFRONT_PRODUCTS);
        $order->billing_term_id = Arr::get($attributes, 'billing_term_id', $order->billing_term_id);
        $order->node_id = Arr::get($attributes, 'node_id', $order->node_id);

        if (array_key_exists('builder_metadata', $attributes)) {
            $order->builder_metadata = $attributes['builder_metadata'];
        }

        $order->saveOrFail();

        return $order;
    }
}

