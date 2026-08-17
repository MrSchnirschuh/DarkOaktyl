<?php

namespace DarkOak\Http\Controllers\Api\Client;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use DarkOak\Services\Templates\ServerTemplateService;
use DarkOak\Http\Controllers\ApplicationApiController;

class ServerTemplateController extends ApplicationApiController
{
    private ServerTemplateService $templateService;

    public function __construct()
    {
        $this->templateService = new ServerTemplateService();
    }

    /**
     * Get templates grouped by category.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->templateService->getTemplatesByCategory();

        return response()->json([
            'object' => 'list',
            'data' => $categories,
        ]);
    }

    /**
     * Get featured templates.
     */
    public function featured(Request $request): JsonResponse
    {
        $templates = $this->templateService->getFeaturedTemplates(
            $request->input('limit', 6)
        );

        return response()->json([
            'object' => 'list',
            'data' => $templates,
        ]);
    }

    /**
     * Get templates by type.
     */
    public function byType(Request $request, string $type): JsonResponse
    {
        $templates = $this->templateService->getTemplatesByType($type);

        return response()->json([
            'object' => 'list',
            'data' => $templates,
        ]);
    }

    /**
     * Get single template.
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        $template = $this->templateService->getTemplate($uuid);

        if (!$template) {
            return response()->json([
                'error' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'data' => $template,
        ]);
    }

    /**
     * Deploy server from template.
     */
    public function deploy(Request $request, string $uuid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'node_id' => 'nullable|integer|exists:nodes,id',
            'memory' => 'nullable|integer|min:0',
            'cpu' => 'nullable|integer|min:0',
            'disk' => 'nullable|integer|min:0',
            'environment' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $server = $this->templateService->deployTemplate(
                $uuid,
                $request->user(),
                $validator->validated()
            );

            return response()->json([
                'data' => [
                    'id' => $server->uuid,
                    'name' => $server->name,
                    'status' => $server->status,
                    'created_at' => $server->created_at->toISOString(),
                ],
                'message' => 'Server deployed successfully',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to deploy server',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search templates.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([
                'error' => 'Search query must be at least 2 characters',
            ], 422);
        }

        $templates = $this->templateService->searchTemplates($query);

        return response()->json([
            'object' => 'list',
            'data' => $templates,
        ]);
    }

    /**
     * Get available template types.
     */
    public function types(): JsonResponse
    {
        return response()->json([
            'data' => [
                ['id' => 'minecraft', 'name' => 'Minecraft'],
                ['id' => 'valheim', 'name' => 'Valheim'],
                ['id' => 'cs2', 'name' => 'CS2'],
                ['id' => 'gmod', 'name' => "Garry's Mod"],
                ['id' => 'rust', 'name' => 'Rust'],
                ['id' => 'factorio', 'name' => 'Factorio'],
                ['id' => 'terraria', 'name' => 'Terraria'],
                ['id' => 'other', 'name' => 'Other'],
            ],
        ]);
    }
}
