<?php

namespace DarkOak\Repositories\Wings;

use DarkOak\Models\Server;
use Webmozart\Assert\Assert;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use DarkOak\Exceptions\Http\Connection\DaemonConnectionException;

/**
 * @method \DarkOak\Repositories\Wings\DaemonCommandRepository setNode(\DarkOak\Models\Node $node)
 * @method \DarkOak\Repositories\Wings\DaemonCommandRepository setServer(\DarkOak\Models\Server $server)
 */
class DaemonCommandRepository extends DaemonRepository
{
    /**
     * Sends a command or multiple commands to a running server instance.
     *
     * @throws \DarkOak\Exceptions\Http\Connection\DaemonConnectionException
     */
    public function send(array|string $command): ResponseInterface
    {
        Assert::isInstanceOf($this->server, Server::class);

        try {
            return $this->getHttpClient()->post(
                sprintf('/api/servers/%s/commands', $this->server->uuid),
                [
                    'json' => ['commands' => is_array($command) ? $command : [$command]],
                ]
            );
        } catch (TransferException $exception) {
            throw new DaemonConnectionException($exception);
        }
    }
}

