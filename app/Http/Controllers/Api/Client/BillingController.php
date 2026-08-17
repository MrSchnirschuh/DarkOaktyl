<?php

namespace DarkOak\Http\Controllers\Api\Client;

use Carbon\Carbon;
use DarkOak\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DarkOak\Models\Billing\BillingRecord;
use DarkOak\Models\Billing\CreditBalance;
use Illuminate\Support\Facades\Validator;
use DarkOak\Models\Billing\CreditTransaction;
use DarkOak\Services\Billing\UsageBillingService;
use DarkOak\Http\Controllers\ApplicationApiController;

class BillingController extends ApplicationApiController
{
    private UsageBillingService $billingService;

    public function __construct()
    {
        $this->billingService = new UsageBillingService();
    }

    /**
     * Get credit balance for authenticated user.
     */
    public function balance(Request $request): JsonResponse
    {
        $creditBalance = CreditBalance::forUser($request->user()->id);

        return response()->json([
            'data' => [
                'balance' => $creditBalance->balance,
                'reserved_balance' => $creditBalance->reserved_balance,
                'available_balance' => $creditBalance->availableBalance(),
                'low_balance_threshold' => $creditBalance->low_balance_threshold,
                'is_low_balance' => $creditBalance->isLowBalance(),
            ],
        ]);
    }

    /**
     * Get billing history.
     */
    public function history(Request $request): JsonResponse
    {
        $query = BillingRecord::where('user_id', $request->user()->id);

        // Filter by server
        if ($request->has('server_id')) {
            $server = Server::where('uuid', $request->input('server_id'))
                ->orWhere('uuidShort', $request->input('server_id'))
                ->first();

            if ($server) {
                $query->where('server_id', $server->id);
            }
        }

        // Date range
        if ($request->has('start_date')) {
            $query->where('billing_period_start', '>=', $request->input('start_date'));
        }
        if ($request->has('end_date')) {
            $query->where('billing_period_end', '<=', $request->input('end_date'));
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $records = $query->with('server:id,name,uuid')
            ->orderBy('billing_period_start', 'desc')
            ->paginate($request->input('per_page', 25));

        return response()->json([
            'object' => 'list',
            'data' => $records->map(fn ($r) => [
                'id' => $r->id,
                'server' => $r->server ? [
                    'id' => $r->server->uuid,
                    'name' => $r->server->name,
                ] : null,
                'billing_period_start' => $r->billing_period_start->toISOString(),
                'billing_period_end' => $r->billing_period_end->toISOString(),
                'hours_billed' => $r->hours_billed,
                'hourly_rate' => $r->hourly_rate,
                'amount' => $r->amount,
                'status' => $r->status,
                'processed_at' => $r->processed_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
            ],
        ]);
    }

    /**
     * Get usage summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $startDate = $request->has('start_date')
            ? Carbon::parse($request->input('start_date'))
            : Carbon::now()->startOfMonth();

        $endDate = $request->has('end_date')
            ? Carbon::parse($request->input('end_date'))
            : Carbon::now();

        $summary = $this->billingService->getUsageSummary(
            $request->user()->id,
            $startDate,
            $endDate
        );

        return response()->json([
            'data' => $summary,
            'period' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
        ]);
    }

    /**
     * Get cost estimate for a server.
     */
    public function estimate(Request $request, string $serverId): JsonResponse
    {
        $server = Server::where('uuid', $serverId)
            ->orWhere('uuidShort', $serverId)
            ->firstOrFail();

        // Check access
        if ($server->owner_id !== $request->user()->id) {
            $isSubuser = $server->subusers()
                ->where('user_id', $request->user()->id)
                ->exists();

            if (!$isSubuser) {
                abort(403, 'You do not have access to this server.');
            }
        }

        $estimate = $this->billingService->getMonthlyEstimate($server);

        return response()->json([
            'data' => $estimate,
        ]);
    }

    /**
     * Get credit transactions.
     */
    public function transactions(Request $request): JsonResponse
    {
        $creditBalance = CreditBalance::forUser($request->user()->id);

        $transactions = $creditBalance->transactions()
            ->paginate($request->input('per_page', 25));

        return response()->json([
            'object' => 'list',
            'data' => $transactions->map(fn (CreditTransaction $t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount' => $t->amount,
                'balance_before' => $t->balance_before,
                'balance_after' => $t->balance_after,
                'description' => $t->description,
                'created_at' => $t->created_at->toISOString(),
            ]),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Add credits (mock endpoint - real would use payment provider).
     */
    public function addCredits(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $amount = $validator->validated()['amount'];

        // In production, this would redirect to Stripe/PayPal
        // For demo, we just add the credits
        $transaction = $this->billingService->addCredits(
            $request->user()->id,
            $amount,
            'Credit purchase',
            'demo'
        );

        return response()->json([
            'data' => [
                'transaction_id' => $transaction->id,
                'amount' => $amount,
                'new_balance' => CreditBalance::forUser($request->user()->id)->balance,
            ],
            'message' => 'Credits added successfully',
        ]);
    }

    /**
     * Update low balance threshold.
     */
    public function updateThreshold(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'threshold' => 'required|numeric|min:0|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $creditBalance = CreditBalance::forUser($request->user()->id);
        $creditBalance->update([
            'low_balance_threshold' => $validator->validated()['threshold'],
        ]);

        return response()->json([
            'data' => [
                'low_balance_threshold' => $creditBalance->low_balance_threshold,
            ],
            'message' => 'Threshold updated successfully',
        ]);
    }
}
