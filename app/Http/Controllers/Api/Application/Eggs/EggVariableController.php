<?php

namespace DarkOak\Http\Controllers\Api\Application\Eggs;

use DarkOak\Models\Egg;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use DarkOak\Models\EggVariable;
use Illuminate\Database\ConnectionInterface;
use DarkOak\Services\Eggs\Variables\VariableUpdateService;
use DarkOak\Services\Eggs\Variables\VariableCreationService;
use DarkOak\Transformers\Api\Application\EggVariableTransformer;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Eggs\Variables\StoreEggVariableRequest;
use DarkOak\Http\Requests\Api\Application\Eggs\Variables\DeleteEggVariableRequest;
use DarkOak\Http\Requests\Api\Application\Eggs\Variables\UpdateEggVariablesRequest;

class EggVariableController extends ApplicationApiController
{
    public function __construct(
        private ConnectionInterface $connection,
        private VariableCreationService $variableCreationService,
        private VariableUpdateService $variableUpdateService,
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

        Activity::event('admin:eggs:variables:create')
            ->subject($egg, $variable)
            ->property('egg', $egg)
            ->property('variable', $variable)
            ->description('A variable was added to an egg')
            ->log();

        return $this->transform($variable, EggVariableTransformer::class);
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

        Activity::event('admin:eggs:variables:update')
            ->subject($egg)
            ->property('egg', $egg)
            ->description('Egg variables were updated')
            ->log();

        return $this->transform($egg->refresh()->variables, EggVariableTransformer::class);
    }

    /**
     * Deletes a single egg variable.
     */
    public function delete(DeleteEggVariableRequest $request, Egg $egg, EggVariable $eggVariable): Response
    {
        EggVariable::query()
            ->where('id', $eggVariable->id)
            ->where('egg_id', $egg->id)
            ->delete();

        Activity::event('admin:eggs:variables:delete')
            ->subject($egg, $eggVariable)
            ->property('egg', $egg)
            ->property('variable', $eggVariable)
            ->description('A variable was removed from an egg')
            ->log();

        return $this->returnNoContent();
    }
}
