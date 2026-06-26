<?php

namespace App\Helpers;

/**
 * Derives the required permission for a route name using the same
 * logic as CheckPermission middleware — so menu visibility stays
 * in sync with route access automatically.
 */
class MenuPermission
{
    private const ALWAYS_ALLOW = [
        'logout', 'master', 'master.tab', 'master.page-builder.get-columns',
        'settings', 'settings.update', 'settings.permission-groups.index',
        'settings.users.sub-users',
        'settings.core-api-sync',
        'settings.core-api-sync.data',
        'settings.core-api-sync.fetch',
        'settings.core-api-sync.sync',
        'settings.core-api-sync.table-data',
        'settings.core-api-sync.modal-data',
        'settings.core-api-sync.empty',
        'settings.core-api-sync.drop',
    ];

    private const STRIP_PREFIXES = ['master.', 'settings.'];

    private const SUB_RESOURCES = [
        'roles'       => 'roles.manage',
        'permissions' => 'permissions.manage',
        'fields'      => 'fields.manage',
    ];

    private const ACTION_MAP = [
        'index'        => 'view',
        'data'         => 'view',
        'show'         => 'view',
        'create'       => 'create',
        'store'        => 'create',
        'edit'         => 'edit',
        'update'       => 'edit',
        'destroy'      => 'delete',
        'bulk-destroy' => 'delete',
        'default'      => 'set-default',
        'current'      => 'set-current',
        'generate'     => 'generate',
        'info'         => 'update',
        'password'     => 'password.change',
    ];

    private const ROUTE_OVERRIDES = [
    ];

    /**
     * Returns true if the current user can access the given route.
     */
    public static function canAccess(string $routeName): bool
    {
        $user = auth()->user();
        if (! $user) return false;

        if (in_array($routeName, self::ALWAYS_ALLOW, true)) return true;

        $permission = self::derive($routeName);

        if ($permission === null) return true;

        return $user->can($permission);
    }

    public static function derive(string $routeName): ?string
    {
        if (isset(self::ROUTE_OVERRIDES[$routeName])) {
            return self::ROUTE_OVERRIDES[$routeName];
        }

        $slug = $routeName;
        foreach (self::STRIP_PREFIXES as $prefix) {
            if (str_starts_with($slug, $prefix)) {
                $slug = substr($slug, strlen($prefix));
                break;
            }
        }

        $parts = explode('.', $slug);

        if (count($parts) === 1) {
            return $parts[0] . '.view';
        }

        foreach (self::SUB_RESOURCES as $subSegment => $subAction) {
            $pos = array_search($subSegment, $parts, true);
            if ($pos !== false && $pos > 0) {
                $parentResource = implode('-', array_slice($parts, 0, $pos));
                return "{$parentResource}.{$subAction}";
            }
        }

        $action   = array_pop($parts);
        $mapped   = self::ACTION_MAP[$action] ?? null;

        if ($mapped === null) return null;

        $resource = implode('-', $parts);
        return "{$resource}.{$mapped}";
    }
}
