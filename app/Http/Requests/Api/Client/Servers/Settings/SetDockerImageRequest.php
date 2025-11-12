<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Settings;

use DarkOak\Models\Server;
use Webmozart\Assert\Assert;
use DarkOak\Models\Permission;
use Illuminate\Validation\Rule;
use DarkOak\Contracts\Http\ClientPermissionsRequest;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class SetDockerImageRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    public function permission(): string
    {
        return Permission::ACTION_STARTUP_DOCKER_IMAGE;
    }

    public function rules(): array
    {
        /** @var \DarkOak\Models\Server $server */
        $server = $this->route()->parameter('server');

        Assert::isInstanceOf($server, Server::class);

        return [
            'docker_image' => ['required', 'string', Rule::in(array_values($server->egg->docker_images))],
        ];
    }
}

