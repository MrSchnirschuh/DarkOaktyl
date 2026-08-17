<?php

namespace DarkOak\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use DarkOak\Services\Billing\UsageBillingService;

class BillingProcessingJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
