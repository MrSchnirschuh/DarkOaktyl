<?php

namespace DarkOak\Http\Controllers\Api\Client\Store;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DarkOak\Models\ServerTemplate;
use Illuminate\Support\Facades\Validator;
use DarkOak\Models\ServerTemplateCategory;
use DarkOak\Services\Templates\TemplateDeployService;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;

class ServerTemplateController extends ClientApiController
{
    /**
     * Get all active server templates grouped by category.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = ServerTemplateCategory::with(['activeTemplates' => function ($query) {
            $query->ordered();
        }])
            ->where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $featured = ServerTemplate::with('category')
            ->featured()
            ->ordered()
            ->limit(6)
            ->get();

        return response()->json([
            'data' => [
                'categories' => $categories->map(function ($category) {
                    return [
                        'uuid' => $category->uuid,
                        'name' => $category->name,
                        'description' => $category->description,
                        'icon' => $category->icon,
                        'templates' => $category->activeTemplates->map(function ($template) {
                            return $this->transformTemplate($template);
                        }),
                    ];
                }),
                'featured' => $featured->map(function ($template) {
                    return $this->transformTemplate($template);
                }),
            ],
        ]);
    }

    /**
     * Get a specific server template by UUID.
     */
    public function show(string $uuid): JsonResponse
    {
        $template = ServerTemplate::with(['category', 'egg', 'nest'])
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => $this->transformTemplate($template, true),
        ]);
    }

    /**
     * Deploy a server from a template.
     */
    public function deploy(Request $request, string $uuid, TemplateDeployService $deployService): JsonResponse
    {
        $template = ServerTemplate::with(['category', 'egg', 'nest'])
            ->where('uuid', $uuid)
            ->where('is_active', true)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:1|max:191',
            'node_id' => 'required|exists:nodes,id',
            'allocation_id' => 'required|exists:allocations,id',
            'custom_resources' => 'nullable|array',
            'custom_resources.memory' => 'nullable|integer|min:0',
            'custom_resources.swap' => 'nullable|integer|min:-1',
            'custom_resources.disk' => 'nullable|integer|min:0',
            'custom_resources.cpu' => 'nullable|integer|min:0',
            'custom_resources.io' => 'nullable|integer|between:10,1000',
            'environment_variables' => 'nullable|array',
            'environment_variables.*.key' => 'required|string',
            'environment_variables.*.value' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            $server = $deployService->handle(
                template: $template,
                user: $request->user(),
                name: $data['name'],
                nodeId: $data['node_id'],
                allocationId: $data['allocation_id'],
                customResources: $data['custom_resources'] ?? [],
                customEnvVars: collect($data['environment_variables'] ?? [])
                    ->mapWithKeys(fn ($item) => [$item['key'] => $item['value']])
                    ->toArray()
            );

            return response()->json([
                'data' => [
                    'server' => [
                        'uuid' => $server->uuid,
                        'name' => $server->name,
                    ],
                    'message' => 'Server is being created and will be ready shortly.',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available game types for filtering.
     */
    public function types(): JsonResponse
    {
        $types = [
            ['key' => ServerTemplate::TYPE_MINECRAFT, 'label' => 'Minecraft'],
            ['key' => ServerTemplate::TYPE_VALHEIM, 'label' => 'Valheim'],
            ['key' => ServerTemplate::TYPE_CS2, 'label' => 'CS2'],
            ['key' => ServerTemplate::TYPE_GMOD, 'label' => 'Garry\'s Mod'],
            ['key' => ServerTemplate::TYPE_RUST, 'label' => 'Rust'],
            ['key' => ServerTemplate::TYPE_FACTORIO, 'label' => 'Factorio'],
            ['key' => ServerTemplate::TYPE_TERRARIA, 'label' => 'Terraria'],
            ['key' => ServerTemplate::TYPE_OTHER, 'label' => 'Other'],
        ];

        return response()->json([
            'data' => $types,
        ]);
    }

    /**
     * Transform a template for API response.
     */
    private function transformTemplate(ServerTemplate $template, bool $detailed = false): array
    {
        $base = [
            'uuid' => $template->uuid,
            'name' => $template->name,
            'description' => $template->description,
            'type' => $template->type,
            'type_label' => $template->getTypeLabel(),
            'image' => $template->image,
            'is_featured' => $template->is_featured,
            'default_resources' => [
                'memory' => $template->default_memory,
                'swap' => $template->default_swap,
                'disk' => $template->default_disk,
                'cpu' => $template->default_cpu,
                'io' => $template->default_io,
            ],
            'feature_limits' => $template->getFeatureLimits(),
        ];

        if ($template->relationLoaded('category') && $template->category) {
            $base['category'] = [
                'uuid' => $template->category->uuid,
                'name' => $template->category->name,
            ];
        }

        if ($detailed) {
            $base['egg'] = $template->relationLoaded('egg') ? [
                'id' => $template->egg->id,
                'uuid' => $template->egg->uuid,
                'name' => $template->egg->name,
            ] : null;

            $base['nest'] = $template->relationLoaded('nest') ? [
                'id' => $template->nest->id,
                'uuid' => $template->nest->uuid,
                'name' => $template->nest->name,
            ] : null;

            $base['environment_variables'] = $template->getEnvironmentVariables();
            $base['startup_command'] = $template->startup_command;
            $base['docker_image'] = $template->docker_image;
        }

        return $base;
    }
}
