<?php

namespace App\Libraries;

use Config\Database;

class AccessControl
{
    public function allows(int $userId, string $permission): bool
    {
        $db = Database::connect();
        $admin = $db->table('role_user')->join('roles', 'roles.id = role_user.role_id')
            ->where('role_user.user_id', $userId)->whereIn('roles.role_name', ['root', 'admin'])->countAllResults();
        if ($admin > 0) {
            return true;
        }
        // Administration cannot be delegated through ordinary business permissions.
        if ($permission === 'security.manage') {
            return false;
        }
        $direct = $db->table('permission_user')->join('permissions', 'permissions.id = permission_user.permission_id')
            ->where('permission_user.user_id', $userId)->where('permissions.permission_name', $permission)->countAllResults();
        if ($direct > 0) {
            return true;
        }
        return $db->table('role_user')->join('permission_role', 'permission_role.role_id = role_user.role_id')
            ->join('permissions', 'permissions.id = permission_role.permission_id')
            ->where('role_user.user_id', $userId)->where('permissions.permission_name', $permission)->countAllResults() > 0;
    }
}
