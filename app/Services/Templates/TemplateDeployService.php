<?php

namespace DarkOak\Services\Templates;

use DarkOak\Models\User;
use DarkOak\Models\Server;
use DarkOak\Models\ServerTemplate;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\ConnectionInterface;
use DarkOak\Services\Servers\ServerCreationService;

class TemplateDeployService
{
    public function __construct(
        private ServerCreationService $serverCreationService,
        private ConnectionInterface $connection,
    ) {
    }

    /**
     * Deploy a server from a template with one-click.
     *
     * @throws \Throwable
     * @throws \DarkOak\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     * @throws \DarkOak\Exceptions\Repository\RecordNotFoundException
     */
    public function handle(
        ServerTemplate $template,
        User $user,
        string $name,
        int $nodeId,
        int $allocationId,
        array $customResources = [],
        array $customEnvVars = [],
    ): Server {
        // Merge default resources with custom resources
        $resources = $this->buildResources($template, $customResources);

        // Merge default environment variables with custom ones
        $environment = $this->buildEnvironmentVariables($template, $customEnvVars);

        // Build server creation data
        $data = [
            'name' => $name,
            'owner_id' => $user->id,
            'node_id' => $nodeId,
            'allocation_id' => $allocationId,
            'egg_id' => $template->egg_id,
            'nest_id' => $template->nest_id,
            'memory' => $resources['memory'],
            'swap' => $resources['swap'],
            'disk' => $resources['disk'],
            'cpu' => $resources['cpu'],
            'io' => $resources['io'],
            'startup' => $template->startup_command,
            'image' => $template->docker_image ?? $template->egg->image,
            'environment' => $environment,
            'skip_scripts' => false,
            'oom_killer' => false,
            'allocation_limit' => $resources['feature_limits']['allocations'] ?? 0,
            'database_limit' => $resources['feature_limits']['databases'] ?? 0,
            'backup_limit' => $resources['feature_limits']['backups'] ?? 0,
            'subuser_limit' => 0,
            'description' => 'Deployed from template: ' . $template->name,
        ];

        // Run pre-install script if defined
        if (!empty($template->pre_install_script)) {
            $this->runPreInstallScript($template, $data);
        }

        Log::info('Deploying server from template', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'user_id' => $user->id,
            'server_name' => $name,
        ]);

        // Create the server
        $server = $this->serverCreationService->handle($data);

        // Run post-install script if defined (this would typically be handled by a job after install completes)
        if (!empty($template->post_install_script)) {
            // Store post-install script reference for later execution
            // This could be stored in a queue job or metadata
            Log::info('Post-install script queued for server', [
                'server_id' => $server->id,
                'template_id' => $template->id,
            ]);
        }

        return $server;
    }

    /**
     * Build the resource configuration for the server.
     */
    private function buildResources(ServerTemplate $template, array $customResources): array
    {
        $defaultResources = $template->getDefaultResources();
        $featureLimits = $template->getFeatureLimits();

        return [
            'memory' => (int) ($customResources['memory'] ?? $defaultResources['memory'] ?? 1024),
            'swap' => (int) ($customResources['swap'] ?? $defaultResources['swap'] ?? 0),
            'disk' => (int) ($customResources['disk'] ?? $defaultResources['disk'] ?? 5000),
            'cpu' => (int) ($customResources['cpu'] ?? $defaultResources['cpu'] ?? 100),
            'io' => (int) ($customResources['io'] ?? $defaultResources['io'] ?? 500),
            'feature_limits' => $featureLimits,
        ];
    }

    /**
     * Build the environment variables for the server.
     */
    private function buildEnvironmentVariables(ServerTemplate $template, array $customEnvVars): array
    {
        $defaultEnvVars = $template->getEnvironmentVariables();

        // Custom env vars override defaults
        return array_merge($defaultEnvVars, $customEnvVars);
    }

    /**
     * Run pre-install script hook.
     */
    private function runPreInstallScript(ServerTemplate $template, array &$data): void
    {
        // This could execute a remote script or local command
        // For now, we just log it
        Log::info('Running pre-install script for template', [
            'template_id' => $template->id,
            'script_length' => strlen($template->pre_install_script),
        ]);
    }

    /**
     * Get recommended resources for a template type.
     */
    public function getRecommendedResources(string $templateType): array
    {
        return match ($templateType) {
            ServerTemplate::TYPE_MINECRAFT => [
                'memory' => 4096,      // 4GB RAM
                'swap' => 1024,       // 1GB Swap
                'disk' => 20000,      // 20GB Disk
                'cpu' => 200,         // 200% CPU
                'io' => 500,
                'feature_limits' => [
                    'databases' => 1,
                    'allocations' => 1,
                    'backups' => 3,
                ],
            ],
            ServerTemplate::TYPE_VALHEIM => [
                'memory' => 4096,
                'swap' => 2048,
                'disk' => 15000,
                'cpu' => 150,
                'io' => 500,
                'feature_limits' => [
                    'databases' => 0,
                    'allocations' => 1,
                    'backups' => 3,
                ],
            ],
            ServerTemplate::TYPE_CS2 => [
                'memory' => 3072,
                'swap' => 1024,
                'disk' => 20000,
                'cpu' => 150,
                'io' => 500,
                'feature_limits' => [
                    'databases' => 0,
                    'allocations' => 1,
                    'backups' => 2,
                ],
            ],
            ServerTemplate::TYPE_RUST => [
                'memory' => 6144,
                'swap' => 2048,
                'disk' => 25000,
                'cpu' => 200,
                'io' => 500,
                'feature_limits' => [
                    'databases' => 1,
                    'allocations' => 1,
                    'backups' => 3,
                ],
            ],
            default => [
                'memory' => 2048,
                'swap' => 1024,
                'disk' => 10000,
                'cpu' => 100,
                'io' => 500,
                'feature_limits' => [
                    'databases' => 0,
                    'allocations' => 1,
                    'backups' => 2,
                ],
            ],
        };
    }
}
