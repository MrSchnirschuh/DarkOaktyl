<?php

namespace DarkOak\Jobs;

use DarkOak\Services\Billing\UsageBillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BillingProcessingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $service = new UsageBillingService();
        $results = $service->processPendingBilling();

        Log::info('Billing processing job completed', [
            'processed' => $results['processed'],
            'failed' => $results['failed'],
            'total_amount' => $results['total_amount'],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Billing processing job failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
