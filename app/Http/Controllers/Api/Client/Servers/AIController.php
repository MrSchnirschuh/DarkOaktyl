<?php

namespace Everest\Http\Controllers\Api\Client\Servers;

use GeminiAPI\Client;
use Everest\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use GeminiAPI\Resources\Parts\TextPart;
use Everest\Exceptions\DisplayException;
use Everest\Http\Controllers\Api\Client\ClientApiController;

class AIController extends ClientApiController
{
    /**
     * AIController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Send an AI generated response to debug a server error.
     */
    public function index(Request $request, Server $server): JsonResponse
    {
        if (!config('modules.ai.enabled')) {
            throw new DisplayException('The AI module is not enabled.');
        }

        $apiKey = config('modules.ai.key');
        if (empty($apiKey)) {
            throw new DisplayException('The AI API key is not configured.');
        }

        $query = $request->input('query');
        if (empty($query) || !is_string($query)) {
            throw new DisplayException('A valid query is required.');
        }

        try {
            $client = new Client($apiKey);

            $response = $client->geminiPro()->generateContent(
                new TextPart($query),
            );

            return response()->json($response->text());
        } catch (\Exception $e) {
            throw new DisplayException('Failed to generate AI response: ' . $e->getMessage());
        }
    }
}
