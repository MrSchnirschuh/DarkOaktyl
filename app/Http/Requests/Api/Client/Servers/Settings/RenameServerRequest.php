<?php

namespace DarkOak\Http\Requests\Api\Client\Servers\Settings;

use DarkOak\Models\Server;
use DarkOak\Models\Permission;
use DarkOak\Contracts\Http\ClientPermissionsRequest;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;

class RenameServerRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Returns the permissions string indicating which permission should be used to
     * validate that the authenticated user has permission to perform this action against
     * the given resource (server).
     */
    public function permission(): string
    {
        return Permission::ACTION_SETTINGS_RENAME;
    }

    /**
     * The rules to apply when validating this request.
     */
    public function rules(): array
    {
        return [
            'name' => Server::getRules()['name'],
            'description' => 'string|nullable',
        ];
    }
}

