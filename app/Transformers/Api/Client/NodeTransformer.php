<?php

namespace Everest\Transformers\Api\Client;

use Everest\Models\Node;
use Everest\Transformers\Api\Transformer;

class NodeTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    /**
     * Return a node transformed into a format that can be consumed by the
     * external client API.
     */
    public function transform(Node $model): array
    {
        $response = $model->toArray();

        $response['created_at'] = self::formatTimestamp($model->created_at);
        $response['updated_at'] = self::formatTimestamp($model->updated_at);

        return $response;
    }
}
