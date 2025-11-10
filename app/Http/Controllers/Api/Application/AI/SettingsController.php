<?php

namespace Everest\Http\Controllers\Api\Application\AI;

use GeminiAPI\Client;
use Everest\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use GeminiAPI\Resources\Parts\TextPart;
use Everest\Http\Requests\Api\Application\AI\AIQueryRequest;
use Everest\Contracts\Repository\SettingsRepositoryInterface;
use Everest\Http\Requests\Api\Application\AI\AISettingsRequest;
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
    public function update(AISettingsRequest $request): Response
    {
        foreach ($request->normalize() as $key => $value) {
            if ($key == 'key' && is_bool($value)) {
                continue;
            }

            $this->settings->set('settings::modules:ai:' . $key, $value);
        }

        Activity::event('admin:ai:update')
            ->property('settings', $request->all())
            ->description('Jexactyl AI settings were updated')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Send a query to Jexactyl AI through Gemini.
     *
     * @throws \Throwable
     */
    public function query(AIQueryRequest $request): JsonResponse
    {
        if (!config('modules.ai.enabled')) {
            throw new \Exception('The Jexactyl AI module is not enabled.');
        }

        $client = new Client(config('modules.ai.key'));

        $response = $client->geminiPro()->generateContent(
            new TextPart($request->input('query')),
        );

        return response()->json($response->text());
    }
}
