<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Models\CustomLink;
use Illuminate\Http\JsonResponse;
use DarkOak\Transformers\Api\Client\LinkTransformer;
use DarkOak\Http\Controllers\ApplicationApiController;

class LinksController extends ApplicationApiController
{
    /**
     * List all custom links visible to clients.
     */
    public function index(): JsonResponse
    {
        $links = CustomLink::query()->where('visible', true)->get();

        return response()->json(
            $this->fractal->collection($links)
            ->transformWith(LinkTransformer::class)
            ->toArray()
        );
    }
}
