<?php

namespace DarkOak\Services\Billing;

use Carbon\Carbon;
use DarkOak\Models\Billing\BillingRecord;
use DarkOak\Models\Billing\CreditBalance;
use DarkOak\Models\Billing\CreditTransaction;
use DarkOak\Models\Server;
use DarkOak\Services\PushNotifications\PushNotificationService;
use Illuminate\Support\Facades\Log;

class UsageBillingService
{
    private PushNotificationService $pushService;

    public function __construct()
    {
        $this->pushService = new PushNotificationService();
    }

    /**
     * Calculate hourly rate for a server based on resources
     */
    public function calculateHourlyRate(Server $server): float
    {
        // Base rate per resource unit
        $rates = config('billing.rates', [
            'memory' => 0.0001,    // per MB per hour
            'cpu' => 0.001,        // per 100% core per hour
            'disk' => 0.00001,     // per MB per hour
            'base' => 0.01,        // base server cost per hour
        ]);

        $memoryCost = $server->memory * $rates['memory'];
        $cpuCost = ($server->cpu / 100) * $rates['cpu'];
        $diskCost = $server->disk * $rates['disk'];
        $baseCost = $rates['base'];

        return $memoryCost + $cpuCost + $diskCost + $baseCost;
    }

    /**
     * Record usage hours for a server
     */
    public function recordUsage(Server $server, Carbon $startTime, Carbon $endTime): BillingRecord
    {
        $hours = $startTime->diffInMinutes($endTime) / 60;
        $hourlyRate = $this->calculateHourlyRate($server);
        $amount = BillingRecord::calculateCost($hours, $hourlyRate);

        $record = BillingRecord::create([
            'user_id' => $server->owner_id,
            'server_id' => $server->id,
            'billing_period_start' => $startTime,
            'billing_period_end' => $endTime,
            'hours_billed' => $hours,
            'hourly_rate' => $hourlyRate,
            'amount' => $amount,
            'status' => BillingRecord::STATUS_PENDING,
            'metadata' => [
                'server_name' => $server->name,
                'memory' => $server->memory,
                'cpu' => $server->cpu,
                'disk' => $server->disk,
            ],
        ]);

        Log::info('Usage recorded', [
            'server_id' => $server->id,
            'hours' => $hours,
            'amount' => $amount,
        ]);

        return $record;
    }

    /**
     * Process pending billing records
     */
    public function processPendingBilling(): array
    {
        $records = BillingRecord::where('status', BillingRecord::STATUS_PENDING)->get();
        $results = [
            'processed' => 0,
            'failed' => 0,
            'total_amount' => 0,
        ];

        foreach ($records as $record) {
            try {
                $this->processBillingRecord($record);
                $results['processed']++;
                $results['total_amount'] += $record->amount;
            } catch (\Exception $e) {
                $record->markAsFailed($e->getMessage());
                $results['failed']++;

                Log::error('Billing processing failed', [
                    'record_id' => $record->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Process a single billing record
     */
    private function processBillingRecord(BillingRecord $record): void
    {
        $creditBalance = CreditBalance::forUser($record->user_id);

        if (!$creditBalance->hasSufficientBalance($record->amount)) {
            // Insufficient funds
            $this->handleInsufficientFunds($record, $creditBalance);
            return;
        }

        // Debit the credit
        $creditBalance->debitCredit(
            $record->amount,
            "Billing for server {$record->server->name} ({$record->hours_billed}h)",
            $record,
            null,
            ['billing_record_id' => $record->id]
        );

        $record->markAsProcessed();

        // Check if balance is now low
        if ($creditBalance->isLowBalance() && !$creditBalance->low_balance_warning_sent) {
            $this->sendLowBalanceWarning($record->user, $creditBalance);
        }
    }

    /**
     * Handle insufficient funds scenario
     */
    private function handleInsufficientFunds(BillingRecord $record, CreditBalance $creditBalance): void
    {
        $record->markAsFailed('Insufficient credit balance');

        // Notify user
        $this->pushService->notifyUser(
            $record->user,
            'billing.payment.failed',
            [
                'title' => 'Payment Failed - Insufficient Credit',
                'body' => "Failed to charge {$record->amount}€ for server {$record->server->name}. Please add more credits.",
                'data' => [
                    'server_id' => $record->server_id,
                    'amount' => $record->amount,
                ],
            ]
        );

        // Optionally suspend server if configured
        if (config('billing.suspend_on_insufficient_funds')) {
            $server = $record->server;
            $server->update(['status' => 'suspended']);

            // Send notification
            $this->pushService->notifyUser(
                $record->user,
                'server.suspended',
                [
                    'title' => 'Server Suspended',
                    'body' => "Server {$server->name} has been suspended due to insufficient credits.",
                    'data' => ['server_id' => $server->id],
                ]
            );
        }
    }

    /**
     * Send low balance warning
     */
    private function sendLowBalanceWarning($user, CreditBalance $creditBalance): void
    {
        $this->pushService->notifyUser(
            $user,
            'alert.billing',
            [
                'title' => 'Low Credit Balance',
                'body' => "Your credit balance ({$creditBalance->balance}€) is below the threshold ({$creditBalance->low_balance_threshold}€). Please add more credits.",
                'data' => [
                    'current_balance' => $creditBalance->balance,
                    'threshold' => $creditBalance->low_balance_threshold,
                ],
            ]
        );

        $creditBalance->markWarningSent();
    }

    /**
     * Get usage summary for user
     */
    public function getUsageSummary(int $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = BillingRecord::where('user_id', $userId)
            ->where('status', BillingRecord::STATUS_PROCESSED);

        if ($startDate) {
            $query->where('billing_period_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('billing_period_end', '<=', $endDate);
        }

        $records = $query->get();

        return [
            'total_hours' => $records->sum('hours_billed'),
            'total_cost' => $records->sum('amount'),
            'server_count' => $records->unique('server_id')->count(),
            'records' => $records->count(),
            'daily_breakdown' => $records->groupBy(fn ($r) => $r->billing_period_start->format('Y-m-d'))
                ->map(fn ($group) => [
                    'hours' => $group->sum('hours_billed'),
                    'cost' => $group->sum('amount'),
                ])
                ->toArray(),
        ];
    }

    /**
     * Get current month estimate
     */
    public function getMonthlyEstimate(Server $server): array
    {
        $hourlyRate = $this->calculateHourlyRate($server);
        $hoursInMonth = 720; // Average month

        return [
            'hourly_rate' => $hourlyRate,
            'monthly_estimate' => round($hourlyRate * $hoursInMonth, 2),
            'daily_estimate' => round($hourlyRate * 24, 2),
            'resources' => [
                'memory' => $server->memory,
                'cpu' => $server->cpu,
                'disk' => $server->disk,
            ],
        ];
    }

    /**
     * Add credits to user account
     */
    public function addCredits(int $userId, float $amount, string $description = 'Credit purchase', string $paymentMethod = 'stripe'): CreditTransaction
    {
        $creditBalance = CreditBalance::forUser($userId);

        $transaction = $creditBalance->addCredit(
            $amount,
            $description,
            null,
            null,
            ['payment_method' => $paymentMethod]
        );

        // Reset warning flag if balance is now sufficient
        if ($creditBalance->balance > $creditBalance->low_balance_threshold) {
            $creditBalance->resetWarningSent();
        }

        return $transaction;
    }
}