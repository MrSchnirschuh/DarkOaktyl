<?php

namespace DarkOak\Repositories\Wings;

use DarkOak\Models\Node;
use Lcobucci\JWT\UnencryptedToken;
use GuzzleHttp\Exception\GuzzleException;
use DarkOak\Exceptions\Http\Connection\DaemonConnectionException;

/**
 * @method \DarkOak\Repositories\Wings\DaemonTransferRepository setNode(\DarkOak\Models\Node $node)
 * @method \DarkOak\Repositories\Wings\DaemonTransferRepository setServer(\DarkOak\Models\Server $server)
 */
class DaemonTransferRepository extends DaemonRepository
{
    /**
     * @throws DaemonConnectionException
     */
    public function notify(Node $targetNode, UnencryptedToken $token): void
    {
        try {
            $this->getHttpClient()->post(sprintf('/api/servers/%s/transfer', $this->server->uuid), [
                'json' => [
                    'server_id' => $this->server->uuid,
                    'url' => $targetNode->getConnectionAddress() . '/api/transfers',
                    'token' => 'Bearer ' . $token->toString(),
                    'server' => [
                        'uuid' => $this->server->uuid,
                        'start_on_completion' => false,
                    ],
                ],
            ]);
        } catch (GuzzleException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}
