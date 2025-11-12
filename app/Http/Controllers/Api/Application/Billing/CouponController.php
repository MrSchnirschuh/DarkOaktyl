<?php

namespace DarkOak\Http\Controllers\Api\Application\Billing;

use Carbon\Carbon;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use DarkOak\Models\Billing\Coupon;
use DarkOak\Models\EmailTemplate;
use DarkOak\Models\User;
use DarkOak\Services\Emails\EmailDispatchService;
use Spatie\QueryBuilder\QueryBuilder;
use DarkOak\Transformers\Api\Application\CouponTransformer;
use DarkOak\Exceptions\Http\QueryValueOutOfRangeHttpException;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\ApplicationApiRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Coupons\GetBillingCouponRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Coupons\GetBillingCouponsRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Coupons\StoreBillingCouponRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Coupons\UpdateBillingCouponRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Coupons\DeleteBillingCouponRequest;
use DarkOak\Http\Requests\Api\Application\Billing\Coupons\SendBillingCouponRequest;

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

        $contextInput = $request->input('context', []);
        $personalize = $request->boolean('personalize');

        if ($personalize) {
            $issuedCoupons = collect();
            $codePrefix = strtoupper((string) $request->input('personalized_code_prefix', ''));
            $codeLength = (int) ($request->input('personalized_code_length') ?? 8);
            $codeLength = max(4, min($codeLength, 24));
            $maxUsages = (int) ($request->input('personalized_max_usages') ?? 1);
            $perUserLimit = (int) ($request->input('personalized_per_user_limit') ?? 1);
            $startsAt = $request->filled('personalized_starts_at') ? Carbon::parse($request->input('personalized_starts_at')) : $coupon->starts_at;
            $expiresAt = $request->filled('personalized_expires_at') ? Carbon::parse($request->input('personalized_expires_at')) : $coupon->expires_at;
            $metadataOverride = $request->input('personalized_metadata');

            DB::transaction(function () use ($userRecipients, $coupon, $request, $codePrefix, $codeLength, $maxUsages, $perUserLimit, $startsAt, $expiresAt, $metadataOverride, &$issuedCoupons) {
                foreach ($userRecipients as $user) {
                    $personalizedCoupon = $coupon->replicate();
                    $personalizedCoupon->uuid = (string) Str::uuid();
                    $personalizedCoupon->code = $this->generateUniqueCouponCode($codePrefix, $codeLength);
                    $personalizedCoupon->usage_count = 0;
                    $personalizedCoupon->max_usages = $maxUsages;
                    $personalizedCoupon->per_user_limit = $perUserLimit;
                    $personalizedCoupon->starts_at = $startsAt;
                    $personalizedCoupon->expires_at = $expiresAt;
                    $personalizedCoupon->created_by_id = $request->user()->id;
                    $personalizedCoupon->updated_by_id = $request->user()->id;
                    $personalizedCoupon->personalized_for_id = $user->id;
                    $personalizedCoupon->parent_coupon_id = $coupon->id;

                    $baseMetadata = $coupon->metadata ?? [];
                    $override = is_array($metadataOverride) ? $metadataOverride : [];
                    $personalizedCoupon->metadata = array_merge($baseMetadata, $override, [
                        'personalized' => true,
                        'parent_coupon_id' => $coupon->id,
                        'parent_coupon_uuid' => $coupon->uuid,
                        'personalized_for' => [
                            'id' => $user->id,
                            'email' => $user->email,
                        ],
                    ]);

                    $personalizedCoupon->save();

                    $issuedCoupons->push([
                        'user' => $user,
                        'coupon' => $personalizedCoupon,
                    ]);
                }
            });

            $issuedIndex = $issuedCoupons->keyBy(fn ($entry) => $entry['user']->id);

            $this->emailDispatcher->send($template, $issuedCoupons->pluck('user'), function ($recipient) use ($contextInput, $coupon, $issuedIndex) {
                $entry = $issuedIndex->get($recipient->id);
                $personalCoupon = $entry['coupon'];

                return array_merge($contextInput, [
                    'coupon' => $personalCoupon,
                    'code' => $personalCoupon->code,
                    'couponCode' => $personalCoupon->code,
                    'parentCoupon' => $coupon,
                ]);
            });

            Activity::event('admin:billing:coupons:send')
                ->property('coupon', $coupon)
                ->property('mode', 'personalized')
                ->property('recipients', $issuedCoupons->map(function ($entry) {
                    /** @var User $user */
                    $user = $entry['user'];

                    return $user->only(['id', 'email']);
                })->values())
                ->property('issued', $issuedCoupons->map(function ($entry) {
                    /** @var User $user */
                    $user = $entry['user'];
                    /** @var Coupon $child */
                    $child = $entry['coupon'];

                    return [
                        'user_id' => $user->id,
                        'coupon_uuid' => $child->uuid,
                        'code' => $child->code,
                    ];
                })->values())
                ->description('Personalized billing coupons were generated and emailed to users')
                ->log();
        } else {
            $context = array_merge($contextInput, [
                'coupon' => $coupon,
                'code' => $coupon->code,
                'couponCode' => $coupon->code,
            ]);

            $this->emailDispatcher->send($template, $recipients, $context);

            Activity::event('admin:billing:coupons:send')
                ->property('coupon', $coupon)
                ->property('mode', 'shared')
                ->property('recipients', $recipients->map(function ($item) {
                    return $item instanceof User ? $item->only(['id', 'email']) : $item;
                })->values())
                ->description('A billing coupon code was emailed to recipients')
                ->log();
        }

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

    protected function generateUniqueCouponCode(string $prefix, int $length): string
    {
        do {
            $code = $prefix . Str::upper(Str::random($length));
        } while (Coupon::query()->where('code', $code)->exists());

        return $code;
    }
}

