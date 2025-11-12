<?php

namespace DarkOak\Http\Controllers\Api\Application\Alerts;

use Ramsey\Uuid\Uuid;
use DarkOak\Facades\Activity;
use Illuminate\Http\JsonResponse;
use DarkOak\Contracts\Repository\SettingsRepositoryInterface;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Alerts\AlertSettingsRequest;

class AlertController extends ApplicationApiController
{
    /**
     * AlertController constructor.
     */
    public function __construct(
        private SettingsRepositoryInterface $settings
    ) {
        parent::__construct();
    }

    /**
     * Update the general alert settings on the Panel.
     *
     * @throws \Throwable
     */
    public function update(AlertSettingsRequest $request): JsonResponse
    {
        $uuid = Uuid::uuid4()->toString();

        $this->settings->set('settings::modules:alert:uuid', $uuid);

        foreach ($request->normalize() as $key => $value) {
            $this->settings->set('settings::modules:alert:' . $key, $value);
        }

        Activity::event('admin:alert:update')
            ->property('settings', $request->all())
            ->description('Alert system was updated with new data')
            ->log();

        return new JsonResponse($uuid);
    }
}

