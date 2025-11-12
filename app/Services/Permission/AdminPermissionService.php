<?php

namespace DarkOak\Services\Permission;

use DarkOak\Models\User;
use DarkOak\Models\AdminRole;

class AdminPermissionService
{
    /**
     * Get the permissions associated with the admin user.
     */
    public function handle(User $user): array
    {
        $permissions = [];

        if ($user->admin_role_id) {
            $role = AdminRole::findOrFail($user->admin_role_id);
            $permissions[] = $role->permissions;
        } else {
            $permissions[] = ['*'];
        }

        return $permissions;
    }
}

