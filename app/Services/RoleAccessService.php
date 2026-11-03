<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Support\PermissionCatalog;
use Spatie\Permission\PermissionRegistrar;

class RoleAccessService
{
    public function apply(string $role, array $permissions, array $privileges, ?string $actor = null, ?string $actorRole = null): void
    {
        PermissionCatalog::syncRole($role, $permissions, $privileges);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        AuditLog::record(
            $actor,
            $actorRole,
            'Role permissions updated',
            null,
            PermissionCatalog::roleLabel($role).': '.count($permissions).' permissions, '
                .(count($privileges) ? implode(', ', $privileges) : 'no privileges'),
        );
    }

    public function describe(string $role, array $permissions, array $privileges): array
    {
        return [
            ['Role', PermissionCatalog::roleLabel($role)],
            ['Permissions', count($permissions).' selected'],
            ['Privileges', $privileges ? implode(', ', $privileges) : 'None'],
        ];
    }
}
