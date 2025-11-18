<?php

namespace DarkOak\Console\Commands\Node;

use DarkOak\Models\Node;
use DarkOak\Models\NodeSnapshot;
use Illuminate\Console\Command;
use DarkOak\Repositories\Wings\DaemonConfigurationRepository;
use Illuminate\Support\Arr;
use Exception;

class CollectNodeSnapshotsCommand extends Command
{
    protected $signature = 'p:node:collect-snapshots';

    protected $description = 'Collect periodic system snapshots from Wings (nodes) and persist them.';

    public function __construct(private DaemonConfigurationRepository $repository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $nodes = Node::all();

        foreach ($nodes as $node) {
            try {
                $data = $this->repository->setNode($node)->getSystemUtilization();

                NodeSnapshot::create([
                    'node_id' => $node->id,
                    'recorded_at' => now(),
                    'cpu_percent' => Arr::get($data, 'cpu'),
                    'memory_used_bytes' => Arr::get($data, 'memory.used', Arr::get($data, 'memory_used')),
                    'memory_total_bytes' => Arr::get($data, 'memory.total', Arr::get($data, 'memory_total')),
                    'disk_used_bytes' => Arr::get($data, 'disk.used', Arr::get($data, 'disk_used')),
                    'disk_total_bytes' => Arr::get($data, 'disk.total', Arr::get($data, 'disk_total')),
                    'disk_read_bytes' => Arr::get($data, 'disk.read_bytes', Arr::get($data, 'disk_read_bytes')),
                    'disk_write_bytes' => Arr::get($data, 'disk.write_bytes', Arr::get($data, 'disk_write_bytes')),
                    'network_rx_bytes' => Arr::get($data, 'network.rx_bytes', Arr::get($data, 'network_rx_bytes')),
                    'network_tx_bytes' => Arr::get($data, 'network.tx_bytes', Arr::get($data, 'network_tx_bytes')),
                ]);
            } catch (Exception $e) {
                // ignore single node failures, but log for troubleshooting
                $this->error('Failed to collect snapshot for node ' . $node->id . ': ' . $e->getMessage());
            }
        }

        return 0;
    }
}
