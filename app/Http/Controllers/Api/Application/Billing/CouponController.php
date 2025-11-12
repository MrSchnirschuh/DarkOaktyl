<?php

namespace Everest\Http\Controllers\Api\Application\Billing;

use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Everest\Models\Billing\Coupon;
use Everest\Models\EmailTemplate;
use Everest\Models\User;
use Everest\Services\Emails\EmailDispatchService;
use Spatie\QueryBuilder\QueryBuilder;
use Everest\Transformers\Api\Application\CouponTransformer;
use Everest\Exceptions\Http\QueryValueOutOfRangeHttpException;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;
use Everest\Http\Requests\Api\Application\ApplicationApiRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\GetBillingCouponRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\GetBillingCouponsRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\StoreBillingCouponRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\UpdateBillingCouponRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\DeleteBillingCouponRequest;
use Everest\Http\Requests\Api\Application\Billing\Coupons\SendBillingCouponRequest;

class CouponController extends ApplicationApiController
{
    public function __construct(private EmailDispatchService $emailDispatcher)
    {
        parent::__construct();
    }

    public function index(GetBillingCouponsRequest $request): array
    {
        $perPage = (int) $request->query('per_page', '50');
        if ($perPage < 1 || $perPage > 100) {
            throw new QueryValueOutOfRangeHttpException('per_page', 1, 100);
        }

        $coupons = QueryBuilder::for(Coupon::query()->with(['term', 'redemptions']))
            ->allowedFilters(['code', 'name', 'type', 'is_active'])
            ->allowedSorts(['code', 'name', 'created_at', 'starts_at', 'expires_at'])
            ->paginate($perPage);

        return $this->fractal->collection($coupons)
            ->transformWith(CouponTransformer::class)
            ->toArray();
    }

    public function store(StoreBillingCouponRequest $request): JsonResponse
    {
        $coupon = DB::transaction(function () use ($request) {
            $attributes = $this->extractAttributes($request, true);

            $attributes['created_by_id'] = $request->user()->id;
            $attributes['updated_by_id'] = $request->user()->id;

            /** @var Coupon $coupon */
            $coupon = Coupon::query()->create($attributes);

            return $coupon->fresh(['term', 'redemptions']);
        });

        Activity::event('admin:billing:coupons:create')
            ->property('coupon', $coupon)
            ->description('A billing coupon was created')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->fractal->item($coupon)
            ->transformWith(CouponTransformer::class)
            ->respond(Response::HTTP_CREATED);
    }

    public function view(GetBillingCouponRequest $request, Coupon $coupon): array
    {
        $coupon->loadMissing(['term', 'redemptions']);

        return $this->fractal->item($coupon)
            ->transformWith(CouponTransformer::class)
            ->toArray();
    }

    public function update(UpdateBillingCouponRequest $request, Coupon $coupon): Response
    {
        DB::transaction(function () use ($request, $coupon) {
            $attributes = $this->extractAttributes($request, false);

            if (!empty($attributes)) {
                $attributes['updated_by_id'] = $request->user()->id;
                $coupon->fill($attributes);
                $coupon->save();
            }
        });

        Activity::event('admin:billing:coupons:update')
            ->property('coupon', $coupon->fresh(['term', 'redemptions']))
            ->property('new_data', $request->all())
            ->description('A billing coupon was updated')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    public function delete(DeleteBillingCouponRequest $request, Coupon $coupon): Response
    {
        $coupon->delete();

        Activity::event('admin:billing:coupons:delete')
            ->property('coupon', $coupon)
            ->description('A billing coupon was deleted')
            ->log();

        Cache::forget('application.billing.analytics');

        return $this->returnNoContent();
    }

    public function send(SendBillingCouponRequest $request, Coupon $coupon): Response
    {
        $template = EmailTemplate::query()->where('uuid', $request->input('template_uuid'))->firstOrFail();

        $userIds = collect($request->input('user_ids', []))
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->values();

        $userRecipients = $userIds->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $userIds)->get();

        $emailRecipients = collect($request->input('emails', []))
            ->filter()
            ->unique()
            ->reject(function ($email) use ($userRecipients) {
                return $userRecipients->contains(fn (User $user) => strcasecmp($user->email, $email) === 0);
            })
            ->values();

        $recipients = $userRecipients->merge($emailRecipients);

        if ($recipients->isEmpty()) {
            return new JsonResponse([
                'message' => 'No valid recipients provided.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $context = array_merge($request->input('context', []), [
            'coupon' => $coupon,
            'code' => $coupon->code,
        ]);

        $this->emailDispatcher->send($template, $recipients, $context);

        Activity::event('admin:billing:coupons:send')
            ->property('coupon', $coupon)
            ->property('recipients', $recipients->map(function ($item) {
                return $item instanceof User ? $item->only(['id', 'email']) : $item;
            })->values())
            ->description('A billing coupon code was emailed to recipients')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * @param StoreBillingCouponRequest|UpdateBillingCouponRequest $request
     */
    protected function extractAttributes(ApplicationApiRequest $request, bool $creating): array
    {
        $fields = [
            'code',
            'name',
            'description',
            'type',
            'value',
            'percentage',
            'max_usages',
            'per_user_limit',
            'applies_to_term_id',
            'starts_at',
            'expires_at',
            'is_active',
            'metadata',
        ];

        $attributes = [];

        foreach ($fields as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);

                if ($field === 'code' && $value !== null) {
                    $value = Str::upper($value);
                }

                if ($field === 'type' && $value !== null) {
                    $value = Str::lower($value);
                }

                if (in_array($field, ['is_active'], true) && $value !== null) {
                    $value = (bool) $value;
                }

                if (in_array($field, ['value', 'percentage'], true) && $value !== null) {
                    $value = (float) $value;
                }

                if (in_array($field, ['max_usages', 'per_user_limit', 'applies_to_term_id'], true) && $value !== null) {
                    $value = (int) $value;
                }

                if (in_array($field, ['starts_at', 'expires_at'], true) && !empty($value)) {
                    $value = Carbon::parse($value);
                }

                $attributes[$field] = $value;
            }
        }

        if ($creating) {
            $attributes['type'] = $attributes['type'] ?? 'amount';
            $attributes['is_active'] = $attributes['is_active'] ?? true;

            if (!array_key_exists('metadata', $attributes)) {
                $attributes['metadata'] = null;
            }
        } else {
            if ($request->has('metadata') && !array_key_exists('metadata', $attributes)) {
                $attributes['metadata'] = null;
            }
        }

        if (($attributes['type'] ?? null) === 'amount') {
            $attributes['percentage'] = null;
        }

        if (($attributes['type'] ?? null) === 'percentage') {
            $attributes['value'] = null;
        }

        return $attributes;
    }
}
