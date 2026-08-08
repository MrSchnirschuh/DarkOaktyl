<?php

namespace DarkOak\Http\Controllers\Api\Application\AI;

use GeminiAPI\Client;
use DarkOak\Models\Setting;
use DarkOak\Facades\Activity;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use GeminiAPI\Resources\Parts\TextPart;
use DarkOak\Http\Controllers\Api\Application\ApplicationApiController;
use DarkOak\Http\Requests\Api\Application\Intelligence;

class SettingsController extends ApplicationApiController
{
    /**
     * SettingsController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Update the AI settings for the Panel.
     *
     * @throws \Throwable
     */
    public function update(Intelligence\UpdateIntelligenceSettingsRequest $request): Response
    {
        foreach ($request->normalize() as $key => $value) {
            if ($key == 'key' && is_bool($value)) {
                continue;
            }

            Setting::set('settings::modules:ai:' . $key, $value);
        }

        Activity::event('admin:ai:update')
            ->property('settings', $request->all())
            ->description('JexpanelAI settings were updated')
            ->log();

        return $this->returnNoContent();
    }

    /**
     * Send a query to JexpanelAI through Gemini.
     *
     * @throws \Throwable
     */
    public function query(Intelligence\QueryRequest $request): JsonResponse
    {
        if (!config('modules.ai.enabled')) {
            throw new \Exception('The JexpanelAI module is not enabled.');
        }

        $client = new Client(config('modules.ai.key'));

        $response = $client->geminiPro()->generateContent(
            new TextPart($request->input('query')),
        );

        return response()->json($response->text());
    }
}
