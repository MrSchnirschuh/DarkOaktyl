<?php

namespace DarkOak\Http\Controllers\Api\Application\Nodes;

use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Models\Node;
use DarkOak\Models\NodeSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class NodeSnapshotController extends ApplicationApiController
{
    /**
     * Return aggregated snapshot metrics (bandwidth) for nodes over a timeframe.
     * Accepts node_ids[]=1&start&end&buckets
     */
    public function index(Request $request): JsonResponse
    {
        $nodeIds = $request->query('node_ids', []);

        if (empty($nodeIds)) {
            $nodeIds = Node::pluck('id')->toArray();
        }

        $start = $request->query('start');
        $end = $request->query('end');

        $endTime = $end ? Carbon::parse($end) : Carbon::now();
        $startTime = $start ? Carbon::parse($start) : Carbon::now()->subDay();

        $buckets = (int) $request->query('buckets', 20);
        $seconds = max(1, $endTime->getTimestamp() - $startTime->getTimestamp());
        $bucketLength = (int) ceil($seconds / $buckets);

        // We'll produce several series per bucket:
        // - network rx/tx bytes (total bytes seen during bucket)
        // - network rx/tx speed (bytes/sec) computed from deltas between consecutive snapshots
        // - disk read/write speed (bytes/sec)
        // - storage percent (avg percent used per bucket)

        $rxBytesSeries = array_fill(0, $buckets, 0);
        $txBytesSeries = array_fill(0, $buckets, 0);
        $rxSpeedSeries = array_fill(0, $buckets, 0.0);
        $txSpeedSeries = array_fill(0, $buckets, 0.0);
        $readSpeedSeries = array_fill(0, $buckets, 0.0);
        $writeSpeedSeries = array_fill(0, $buckets, 0.0);
        $storageSum = array_fill(0, $buckets, 0.0);
        $storageCount = array_fill(0, $buckets, 0);

        $snapshots = NodeSnapshot::query()
            ->whereIn('node_id', $nodeIds)
            ->whereBetween('recorded_at', [$startTime->toDateTimeString(), $endTime->toDateTimeString()])
            ->orderBy('node_id')
            ->orderBy('recorded_at')
            ->get();

        // Group by node and compute deltas between consecutive samples; assign delta to bucket of later timestamp
        $grouped = $snapshots->groupBy('node_id');
        foreach ($grouped as $nodeId => $items) {
            $prev = null;
            foreach ($items as $s) {
                // storage percent: compute from snapshot and add to bucket accumulator
                if ($s->disk_total_bytes && $s->disk_total_bytes > 0) {
                    $percent = ($s->disk_used_bytes / max(1, $s->disk_total_bytes)) * 100.0;
                    $ts = Carbon::parse($s->recorded_at)->getTimestamp();
                    $index = (int) floor(($ts - $startTime->getTimestamp()) / $bucketLength);
                    if ($index < 0) $index = 0;
                    if ($index >= $buckets) $index = $buckets - 1;
                    $storageSum[$index] += $percent;
                    $storageCount[$index]++;
                }

                if ($prev) {
                    $deltaSeconds = max(1, Carbon::parse($s->recorded_at)->getTimestamp() - Carbon::parse($prev->recorded_at)->getTimestamp());

                    $rxDelta = max(0, ($s->network_rx_bytes ?? 0) - ($prev->network_rx_bytes ?? 0));
                    $txDelta = max(0, ($s->network_tx_bytes ?? 0) - ($prev->network_tx_bytes ?? 0));
                    $readDelta = max(0, ($s->disk_read_bytes ?? 0) - ($prev->disk_read_bytes ?? 0));
                    $writeDelta = max(0, ($s->disk_write_bytes ?? 0) - ($prev->disk_write_bytes ?? 0));

                    $ts = Carbon::parse($s->recorded_at)->getTimestamp();
                    $index = (int) floor(($ts - $startTime->getTimestamp()) / $bucketLength);
                    if ($index < 0) $index = 0;
                    if ($index >= $buckets) $index = $buckets - 1;

                    // accumulate raw bytes and speeds (bytes/sec)
                    $rxBytesSeries[$index] += $rxDelta;
                    $txBytesSeries[$index] += $txDelta;

                    $rxSpeedSeries[$index] += ($rxDelta / $deltaSeconds);
                    $txSpeedSeries[$index] += ($txDelta / $deltaSeconds);

                    $readSpeedSeries[$index] += ($readDelta / $deltaSeconds);
                    $writeSpeedSeries[$index] += ($writeDelta / $deltaSeconds);
                }
                $prev = $s;
            }
        }

        $labels = [];
        for ($i = 0; $i < $buckets; $i++) {
            $labels[] = $startTime->copy()->addSeconds($i * $bucketLength)->toIso8601String();
        }

        // Compute average storage percent per bucket
        $storagePercentSeries = [];
        for ($i = 0; $i < $buckets; $i++) {
            if ($storageCount[$i] > 0) {
                $storagePercentSeries[] = $storageSum[$i] / $storageCount[$i];
            } else {
                $storagePercentSeries[] = null;
            }
        }

        return new JsonResponse([
            'nodes' => Node::whereIn('id', $nodeIds)->get(['id', 'name']),
            'bandwidth' => [
                'labels' => $labels,
                'rx_bytes' => $rxBytesSeries,
                'tx_bytes' => $txBytesSeries,
                'rx_speed_bps' => $rxSpeedSeries,
                'tx_speed_bps' => $txSpeedSeries,
            ],
            'io' => [
                'read_speed_bps' => $readSpeedSeries,
                'write_speed_bps' => $writeSpeedSeries,
            ],
            'storage' => [
                'percent' => $storagePercentSeries,
            ],
        ]);
    }
}
