<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Http\Controllers\ApplicationApiController;
use DarkOak\Models\CustomLink;
use DarkOak\Transformers\Api\Client\LinkTransformer;
use Illuminate\Http\JsonResponse;

class LinksController extends ApplicationApiController
{
    /**
     * List all custom links visible to clients.
     */
    public function index(): JsonResponse
    {
        $links = CustomLink::query()->where('visible', true)->get();

        return response()->json($this->fractal->collection($links)
            ->transformWith(LinkTransformer::class)
            ->toArray()
        );
    }
}