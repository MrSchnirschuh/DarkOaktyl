<?php

namespace DarkOak\Transformers\Api\Application;

use DarkOak\Models\EggVariable;
use DarkOak\Models\ServerVariable;
use DarkOak\Transformers\Api\Transformer;

class ServerVariableTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return ServerVariable::RESOURCE_NAME;
    }

    /**
     * Return a generic transformed server variable array.
     */
    public function transform(EggVariable $model): array
    {
        return $model->toArray();
    }
}

