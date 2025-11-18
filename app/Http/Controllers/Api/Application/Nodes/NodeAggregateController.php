<?php

namespace DarkOak\Http\Controllers\Api\Application\Nodes;

use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Models\Node;
use DarkOak\Models\Backup;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class NodeAggregateController extends ApplicationApiController
{
    /**
     * Return aggregated metrics for nodes.
     * Supports node_ids[]=1&node_ids[]=2 and start/end ISO timestamps.
     */
    public function index(Request $request): JsonResponse
    {
        $nodeIds = $request->query('node_ids', []);

        // fallback: all nodes
        if (empty($nodeIds)) {
            $nodeIds = Node::pluck('id')->toArray();
        }

        $start = $request->query('start');
        $end = $request->query('end');

        $endTime = $end ? Carbon::parse($end) : Carbon::now();
        $startTime = $start ? Carbon::parse($start) : Carbon::now()->subDay();

        // number of buckets — keep aligned with frontend chart points (20)
        $buckets = (int) $request->query('buckets', 20);
        $seconds = max(1, $endTime->getTimestamp() - $startTime->getTimestamp());
        $bucketLength = (int) ceil($seconds / $buckets);

        // Prepare empty series
        $series = array_fill(0, $buckets, 0);

        // Query backups on servers that belong to the selected nodes
        $backupQuery = Backup::query()
            ->select('backups.*')
            ->join('servers', 'backups.server_id', '=', 'servers.id')
            ->whereIn('servers.node_id', $nodeIds)
            ->whereBetween('backups.created_at', [$startTime->toDateTimeString(), $endTime->toDateTimeString()])
            ->where(function ($q) {
                $q->whereNull('backups.completed_at')->orWhere('backups.is_successful', true);
            })
            ->get(['backups.created_at']);

        foreach ($backupQuery as $b) {
            $ts = Carbon::parse($b->created_at)->getTimestamp();
            $index = (int) floor(($ts - $startTime->getTimestamp()) / $bucketLength);
            if ($index < 0) $index = 0;
            if ($index >= $buckets) $index = $buckets - 1;
            $series[$index]++;
        }

        // Build timestamp labels for buckets (ISO)
        $labels = [];
        for ($i = 0; $i < $buckets; $i++) {
            $labels[] = $startTime->copy()->addSeconds($i * $bucketLength)->toIso8601String();
        }

        return new JsonResponse([
            'nodes' => Node::whereIn('id', $nodeIds)->get(['id', 'name']),
            'backups' => [
                'total' => array_sum($series),
                'labels' => $labels,
                'series' => $series,
            ],
        ]);
    }
}
