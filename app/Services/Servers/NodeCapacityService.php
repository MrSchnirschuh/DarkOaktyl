<?php

namespace Everest\Services\Servers;

use Everest\Exceptions\Service\Deployment\NoViableNodeException;
use Everest\Models\Node;
use Everest\Repositories\Wings\DaemonConfigurationRepository;
use Illuminate\Support\Facades\Log;
use Everest\Exceptions\Http\Connection\DaemonConnectionException;

class NodeCapacityService
{
    public function __construct(private DaemonConfigurationRepository $configurationRepository)
    {
    }

    /**
     * Ensures that the provided node has sufficient capacity for the requested resources.
     *
     * This check first validates against the panel's recorded allocation data and then
     * performs a secondary validation against the Super-Daemon metrics whenever they are
     * available. If either check fails a NoViableNodeException will be thrown.
     */
    public function assertCanAllocate(Node $node, int $memoryMb, int $diskMb): void
    {
        if ($memoryMb <= 0 && $diskMb <= 0) {
            return;
        }

        if (!$node->isViable($memoryMb, $diskMb)) {
            throw new NoViableNodeException(trans('exceptions.deployment.no_viable_nodes'));
        }

        if (!$this->shouldCheckDaemonCapacity()) {
            return;
        }

        $supercharged = false;

        try {
            $info = $this->configurationRepository->setNode($node)->getSystemInformation();
            $supercharged = (bool) ($info['system']['supercharged'] ?? $info['supercharged'] ?? false);
        } catch (DaemonConnectionException $exception) {
            Log::debug('Failed to retrieve system information for node capacity validation.', [
                'node_id' => $node->id,
                'exception' => $exception->getMessage(),
            ]);

            return;
        }

        if (!$supercharged) {
            return;
        }

        try {
            $utilization = $this->configurationRepository->setNode($node)->getSystemUtilization();
        } catch (DaemonConnectionException $exception) {
            Log::debug('Failed to retrieve utilization data for node capacity validation.', [
                'node_id' => $node->id,
                'exception' => $exception->getMessage(),
            ]);

            return;
        }

        $memoryAvailable = $this->bytesToMegabytes($utilization['memory_total'] ?? 0)
            - $this->bytesToMegabytes($utilization['memory_used'] ?? 0);
        $diskAvailable = $this->bytesToMegabytes($utilization['disk_total'] ?? 0)
            - $this->bytesToMegabytes($utilization['disk_used'] ?? 0);

        if (($memoryMb > 0 && $memoryAvailable < $memoryMb) || ($diskMb > 0 && $diskAvailable < $diskMb)) {
            throw new NoViableNodeException(trans('exceptions.deployment.no_viable_nodes'));
        }
    }

    private function shouldCheckDaemonCapacity(): bool
    {
        return (bool) config('modules.billing.enabled', false);
    }

    private function bytesToMegabytes(int|float $bytes): int
    {
        return (int) floor($bytes / 1024 / 1024);
    }
}
