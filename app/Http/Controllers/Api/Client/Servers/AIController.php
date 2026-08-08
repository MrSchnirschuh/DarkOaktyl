<?php

namespace DarkOak\Http\Controllers\Api\Client\Servers;

use GeminiAPI\Client;
use DarkOak\Models\Server;
use Illuminate\Http\JsonResponse;
use GeminiAPI\Resources\Parts\TextPart;
use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Http\Requests\Api\Client\Servers\QueryAIRequest;

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
    public function index(QueryAIRequest $request, Server $server): JsonResponse
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
