<?php

namespace DarkOak\Http\Controllers\Api\Application\Eggs;

use DarkOak\Models\Egg;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use DarkOak\Models\EggVariable;
use Illuminate\Database\ConnectionInterface;
use DarkOak\Services\Eggs\Variables\VariableUpdateService;
use DarkOak\Services\Eggs\Variables\VariableCreationService;
use DarkOak\Transformers\Api\Application\EggVariableTransformer;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Eggs\Variables\StoreEggVariableRequest;
use DarkOak\Http\Requests\Api\Application\Eggs\Variables\UpdateEggVariablesRequest;

class EggVariableController extends ApplicationApiController
{
    public function __construct(
        private ConnectionInterface $connection,
        private VariableCreationService $variableCreationService,
        private VariableUpdateService $variableUpdateService
    ) {
        parent::__construct();
    }

    /**
     * Creates a new egg variable.
     *
     * @throws \DarkOak\Exceptions\Model\DataValidationException
     * @throws \DarkOak\Exceptions\Service\Egg\Variable\BadValidationRuleException
     * @throws \DarkOak\Exceptions\Service\Egg\Variable\ReservedVariableNameException
     */
    public function store(StoreEggVariableRequest $request, Egg $egg): array
    {
        $variable = $this->variableCreationService->handle($egg->id, $request->validated());

        return $this->fractal->item($variable)
            ->transformWith(EggVariableTransformer::class)
            ->toArray();
    }

    /**
     * Updates multiple egg variables.
     *
     * @throws \Throwable
     */
    public function update(UpdateEggVariablesRequest $request, Egg $egg): array
    {
        $validated = $request->validated();

        $this->connection->transaction(function () use ($egg, $validated) {
            foreach ($validated as $data) {
                $this->variableUpdateService->handle($egg, $data);
            }
        });

        return $this->fractal->collection($egg->refresh()->variables)
            ->transformWith(EggVariableTransformer::class)
            ->toArray();
    }

    /**
     * Deletes a single egg variable.
     */
    public function delete(Request $request, Egg $egg, EggVariable $eggVariable): Response
    {
        EggVariable::query()
            ->where('id', $eggVariable->id)
            ->where('egg_id', $egg->id)
            ->delete();

        return $this->returnNoContent();
    }
}

