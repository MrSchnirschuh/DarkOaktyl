<?php

namespace DarkOak\Http\Controllers\Api\Application\Settings;

use DarkOak\Models\Setting;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Settings\UpdateApplicationModeRequest;

class ModeController extends ApplicationApiController
{
    /**
     * ModeController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Update the selected Panel mode.
     *
     * @throws \Throwable
     */
    public function update(UpdateApplicationModeRequest $request): Response
    {
        Setting::set('settings::app:mode', $request['mode']);

        Activity::event('admin:settings:mode')
            ->property('mode', $request['mode'])
            ->description('The panel mode was changed')
            ->log();

        return $this->returnNoContent();
    }
}
