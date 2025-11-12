<?php

namespace DarkOak\Http\Controllers\Api\Client;

use DarkOak\Models\CustomLink;
use DarkOak\Transformers\Api\Client\LinkTransformer;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class LinkController extends ClientApiController
{
    /**
     * Returns a list of all visible links.
     */
    public function index(ClientApiRequest $request): array
    {
        $links = CustomLink::where('visible', true)->get();

        return $this->fractal->collection($links)
            ->transformWith(LinkTransformer::class)
            ->toArray();
    }
}

