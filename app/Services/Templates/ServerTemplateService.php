<?php

namespace DarkOak\Services\Templates;

use DarkOak\Models\ServerTemplate;
use DarkOak\Models\ServerTemplateCategory;
use DarkOak\Models\Server;
use DarkOak\Models\Node;
use DarkOak\Models\User;
use DarkOak\Services\Servers\ServerCreationService;
use Illuminate\Support\Facades\Log;

class ServerTemplateService
{
    private ServerCreationService $creationService;

    public function __construct()
    {
        $this->creationService = new ServerCreationService();
    }

    /**
     * Get all templates grouped by category
     */
    public function getTemplatesByCategory(): array
    {
        $categories = ServerTemplateCategory::with(['templates' => fn ($q) => $q->active()->ordered()])
            ->ordered()
            ->get();

        return $categories->map(fn ($cat) => [
            'id' => $cat->uuid,
            'name' => $cat->name,
            'icon' => $cat->icon,
            'description' => $cat->description,
            'templates' => $cat->templates->map(fn ($t) => $this->formatTemplate($t)),
        ])->toArray();
    }

    /**
     * Get featured templates
     */
    public function getFeaturedTemplates(int $limit = 6): array
    {
        return ServerTemplate::featured()
            ->with('category')
            ->limit($limit)
            ->get()
            ->map(fn ($t) => $this->formatTemplate($t))
            ->toArray();
    }

    /**
     * Get templates by type
     */
    public function getTemplatesByType(string $type): array
    {
        return ServerTemplate::where('type', $type)
            ->active()
            ->with('category')
            ->ordered()
            ->get()
            ->map(fn ($t) => $this->formatTemplate($t))
            ->toArray();
    }

    /**
     * Get single template with full details
     */
    public function getTemplate(string $uuid): ?array
    {
        $template = ServerTemplate::where('uuid', $uuid)
            ->with(['category', 'egg', 'nest'])
            ->first();

        if (!$template) {
            return null;
        }

        return $this->formatTemplateDetail($template);
    }

    /**
     * Deploy a server from template
     */
    public function deployTemplate(
        string $templateUuid,
        User $user,
        array $options
    ): Server {
        $template = ServerTemplate::where('uuid', $templateUuid)->firstOrFail();

        // Prepare server data from template
        $serverData = [
            'name' => $options['name'] ?? $template->name . ' Server',
            'owner_id' => $user->id,
            'node_id' => $options['node_id'] ?? Node::first()?->id,
            'allocation_id' => $options['allocation_id'] ?? null,
            'nest_id' => $template->nest_id,
            'egg_id' => $template->egg_id,
            'memory' => $options['memory'] ?? $template->default_memory,
            'swap' => $options['swap'] ?? $template->default_swap,
            'disk' => $options['disk'] ?? $template->default_disk,
            'cpu' => $options['cpu'] ?? $template->default_cpu,
            'io' => $options['io'] ?? $template->default_io,
            'startup' => $template->startup_command ?? $template->egg?->startup,
            'image' => $template->docker_image ?? $template->egg?->image ?? 'alpine:latest',
            'environment' => array_merge(
                $template->getEnvironmentVariables(),
                $options['environment'] ?? []
            ),
            'allocation_limits' => $template->getFeatureLimits()['allocations'] ?? 0,
            'database_limits' => $template->getFeatureLimits()['databases'] ?? 0,
            'backup_limits' => $template->getFeatureLimits()['backups'] ?? 0,
        ];

        // Create server
        $server = $this->creationService->handle($serverData);

        // Execute pre-install script if present
        if ($template->pre_install_script) {
            $this->executeScript($server, $template->pre_install_script, 'pre-install');
        }

        // Execute install script if present
        if ($template->install_script) {
            $this->executeScript($server, $template->install_script, 'install');
        }

        // Execute post-install script if present
        if ($template->post_install_script) {
            $this->executeScript($server, $template->post_install_script, 'post-install');
        }

        Log::info('Server deployed from template', [
            'server_id' => $server->id,
            'template_id' => $template->id,
            'user_id' => $user->id,
        ]);

        return $server;
    }

    /**
     * Format template for API response
     */
    private function formatTemplate(ServerTemplate $template): array
    {
        return [
            'id' => $template->uuid,
            'name' => $template->name,
            'description' => $template->description,
            'type' => $template->type,
            'type_label' => $template->getTypeLabel(),
            'image' => $template->image,
            'default_resources' => [
                'memory' => $template->default_memory,
                'cpu' => $template->default_cpu,
                'disk' => $template->default_disk,
                'swap' => $template->default_swap,
                'io' => $template->default_io,
            ],
            'is_featured' => $template->is_featured,
            'category' => $template->category?->name,
        ];
    }

    /**
     * Format template with full details
     */
    private function formatTemplateDetail(ServerTemplate $template): array
    {
        return array_merge($this->formatTemplate($template), [
            'egg' => [
                'id' => $template->egg?->uuid,
                'name' => $template->egg?->name,
                'author' => $template->egg?->author,
            ],
            'nest' => [
                'id' => $template->nest?->uuid,
                'name' => $template->nest?->name,
            ],
            'startup_command' => $template->startup_command,
            'docker_image' => $template->docker_image,
            'environment_variables' => $template->getEnvironmentVariables(),
            'feature_limits' => $template->getFeatureLimits(),
            'category_id' => $template->category?->uuid,
        ]);
    }

    /**
     * Execute deployment script
     */
    private function executeScript(Server $server, string $script, string $phase): void
    {
        try {
            // Queue script execution
            dispatch(function () use ($server, $script, $phase) {
                app(\DarkOak\Repositories\Wings\DaemonCommandRepository::class)
                    ->setServer($server)
                    ->send($script);

                Log::info("Template {$phase} script executed", [
                    'server_id' => $server->id,
                    'phase' => $phase,
                ]);
            })->delay(now()->addSeconds(10)); // Delay to let server start

        } catch (\Exception $e) {
            Log::error("Template {$phase} script failed", [
                'server_id' => $server->id,
                'phase' => $phase,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Search templates
     */
    public function searchTemplates(string $query): array
    {
        return ServerTemplate::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->active()
            ->with('category')
            ->ordered()
            ->get()
            ->map(fn ($t) => $this->formatTemplate($t))
            ->toArray();
    }
}