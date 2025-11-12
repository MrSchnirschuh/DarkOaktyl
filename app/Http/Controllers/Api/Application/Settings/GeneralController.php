<?php

namespace DarkOak\Http\Controllers\Api\Application\Settings;

use Illuminate\Http\Response;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Settings\GeneralSettingsRequest;

class GeneralController extends ApplicationApiController
{
    /**
     * GeneralController constructor.
     */
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    /**
     * Update the general settings on the Panel.
     *
     * @throws \Throwable
     */
    public function update(GeneralSettingsRequest $request): Response
    {
        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::app:' . $key, $value);
        }

        return $this->returnNoContent();
    }
}

