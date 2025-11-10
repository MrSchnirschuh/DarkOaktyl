<?php

namespace Everest\Http\Controllers\Api\Application\Webhooks;

use Illuminate\Http\Request;
use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Everest\Contracts\Repository\SettingsRepositoryInterface;
use Everest\Http\Controllers\Api\Application\ApplicationApiController;

class SettingsController extends ApplicationApiController
{
    /**
     * SettingsController constructor.
     */
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    /**
     * Update the AI settings for the Panel.
     *
     * @throws \Throwable
     */
    public function update(Request $request): Response
    {
        $this->settings->set('settings::modules:webhooks:' . $request->input('key'), $request->input('value'));

        Activity::event('admin:webhooks:update')
            ->property('settings', $request->all())
            ->description('Jexactyl webhook settings were updated')
            ->log();

        return $this->returnNoContent();
    }
}
