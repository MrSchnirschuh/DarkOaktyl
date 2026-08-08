<?php

namespace DarkOak\Http\Controllers\Api\Application\Settings;

use DarkOak\Models\Setting;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Settings\UpdateApplicationSettingsRequest;

class GeneralController extends ApplicationApiController
{
    /**
     * GeneralController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Update the general settings on the Panel.
     *
     * @throws \Throwable
     */
    public function update(UpdateApplicationSettingsRequest $request): Response
    {
        foreach ($request->normalize() as $key => $value) {
            Setting::set('settings::' . $key, $value);
        }

        Activity::event('admin:settings:update')
            ->property('settings', $request->normalize())
            ->description('The general panel settings were updated')
            ->log();

        return $this->returnNoContent();
    }
}
