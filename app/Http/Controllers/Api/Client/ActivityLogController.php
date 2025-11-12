<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Models\ActivityLog;
use Illuminate\Support\Facades\Cache;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Transformers\Api\Client\ActivityLogTransformer;

class ActivityLogController extends ClientApiController
{
    /**
     * Returns a paginated set of the user's activity logs.
     */
    public function __invoke(ClientApiRequest $request): array
    {
        $user = $request->user();
        $cacheKey = sprintf(
            'client.account.activity.%d.%s',
            $user->id,
            sha1(json_encode($request->query()))
        );

        return Cache::remember(
            $cacheKey,
            now()->addSeconds(20),
            function () use ($request, $user) {
                $perPage = (int) $request->query('per_page', 5);
                $perPage = max(1, min($perPage, 100));

                $filters = (array) $request->query('filter', []);
                $sortParam = (string) $request->query('sort', '-timestamp');
                $sortDirection = str_starts_with($sortParam, '-') ? 'desc' : 'asc';
                $sortColumn = ltrim(explode(',', $sortParam)[0] ?? '-timestamp', '-');

                if ($sortColumn !== 'timestamp') {
                    $sortColumn = 'timestamp';
                    $sortDirection = 'desc';
                }

                $query = ActivityLog::query()
                    ->without('subjects')
                    ->select([
                        'activity_logs.id',
                        'activity_logs.batch',
                        'activity_logs.event',
                        'activity_logs.ip',
                        'activity_logs.is_api',
                        'activity_logs.description',
                        'activity_logs.properties',
                        'activity_logs.timestamp',
                        'activity_logs.actor_type',
                        'activity_logs.actor_id',
                    ])
                    ->join('activity_log_subjects as als', function ($join) use ($user) {
                        $join->on('als.activity_log_id', '=', 'activity_logs.id')
                            ->where('als.subject_type', '=', $user->getMorphClass())
                            ->where('als.subject_id', '=', $user->id);
                    })
                    ->where('activity_logs.is_admin', false)
                    ->where('activity_logs.timestamp', '>=', now()->subMonths(6))
                    ->whereNotIn('activity_logs.event', ActivityLog::DISABLED_EVENTS)
                    ->with('actor')
                    ->orderBy('activity_logs.' . $sortColumn, $sortDirection)
                    ->distinct();

                if (!empty($filters['event'])) {
                    $query->where('activity_logs.event', 'like', '%' . $filters['event'] . '%');
                }

                if (!empty($filters['ip'])) {
                    $query->where('activity_logs.ip', 'like', '%' . $filters['ip'] . '%');
                }

                $activity = $query->paginate($perPage)->appends($request->query());

                return $this->fractal->collection($activity)
                    ->transformWith(ActivityLogTransformer::class)
                    ->toArray();
            }
        );
    }
}

