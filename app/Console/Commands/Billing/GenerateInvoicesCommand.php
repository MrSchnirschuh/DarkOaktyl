<?php

namespace DarkOak\Console\Commands\Billing;

use Illuminate\Console\Command;
use DarkOak\Models\Billing\Order;
use DarkOak\Jobs\Billing\GenerateInvoiceJob;

class GenerateInvoicesCommand extends Command
{
    protected $description = 'An automated task to generate invoices for processed orders that do not have one yet.';

    protected $signature = 'p:billing:generate-invoices';

    /**
     * Handle command execution.
     */
    public function handle()
    {
        Order::where('status', Order::STATUS_PROCESSED)
            ->whereDoesntHave('invoice')
            ->get()
            ->each(function (Order $order) {
                $this->info("queuing invoice generation for order {$order->id}");
                GenerateInvoiceJob::dispatch($order);
            });
    }
}
