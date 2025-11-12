<?php

namespace Everest\Services\Emails;

class EmailEventRegistry
{
    public function all(): array
    {
        return [
            [
                'key' => \Everest\Events\User\Created::class,
                'label' => 'User created',
                'description' => 'Triggered when a new user account is created.',
                'context' => ['user'],
            ],
            [
                'key' => \Everest\Events\User\Deleted::class,
                'label' => 'User deleted',
                'description' => 'Triggered when a user account is removed from the panel.',
                'context' => ['user'],
            ],
            [
                'key' => \Everest\Events\Server\Created::class,
                'label' => 'Server provisioned',
                'description' => 'Triggered when a server has been created and handed off to the daemon.',
                'context' => ['server', 'user'],
            ],
            [
                'key' => \Everest\Events\Server\Deleted::class,
                'label' => 'Server deleted',
                'description' => 'Triggered when a server is deleted from the panel.',
                'context' => ['server', 'user'],
            ],
            [
                'key' => \Everest\Events\Subuser\Created::class,
                'label' => 'Subuser added',
                'description' => 'Triggered when a new subuser is assigned to a server.',
                'context' => ['user', 'server'],
            ],
        ];
    }
}
