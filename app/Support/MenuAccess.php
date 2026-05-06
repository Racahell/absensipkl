<?php

namespace App\Support;

use App\Models\Menu;
use App\Models\MenuPermission;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class MenuAccess
{
    private static array $cache = [];

    public static function canAccess(string $role, string $menuKey): bool
    {
        $role = self::normalizeRole($role);

        if (in_array($role, ['superadmin', 'owner'], true)) {
            return true;
        }

        if (! self::menuTablesReady()) {
            return false;
        }

        if (! isset(self::$cache[$role])) {
            try {
                self::$cache[$role] = MenuPermission::query()
                    ->select('menus.key', 'menu_permissions.is_allowed')
                    ->join('menus', 'menus.id', '=', 'menu_permissions.menu_id')
                    ->where('menu_permissions.role', $role)
                    ->pluck('menu_permissions.is_allowed', 'menus.key')
                    ->map(fn ($value) => (bool) $value)
                    ->toArray();
            } catch (QueryException) {
                return false;
            }
        }

        if (! array_key_exists($menuKey, self::$cache[$role])) {
            return false;
        }

        return (bool) (self::$cache[$role][$menuKey] ?? false);
    }

    private static function normalizeRole(string $role): string
    {
        return match ($role) {
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
            'pembimbing' => 'pembimbing_pkl',
            default => $role,
        };
    }

    private static function menuTablesReady(): bool
    {
        try {
            return Schema::hasTable('menus') && Schema::hasTable('menu_permissions');
        } catch (QueryException) {
            return false;
        }
    }
}
