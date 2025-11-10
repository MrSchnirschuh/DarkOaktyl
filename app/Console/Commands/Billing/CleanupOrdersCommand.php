<?php

namespace Everest\Console\Commands\Billing;

use Everest\Models\User;
use Illuminate\Console\Command;
use Everest\Models\Billing\Order;

class CleanupOrdersCommand extends Command
{
    protected $description = 'An automated task to delete and edit billing orders.';

    protected $signature = 'p:billing:cleanup-orders';

    /**
     * CleanupOrdersCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle command execution.
     */
    public function handle()
    {
        foreach (Order::all() as $order) {
            $user = User::find($order->user_id) ?? null;

            if (!$user) {
                $order->delete();
                continue;
            }

            switch ($order->status) {
                case 'pending':
                    $order->update(['status' => Order::STATUS_EXPIRED]);
                    break;
                case 'expired':
                    $order->delete();
                    break;
            }
        }
    }
}
