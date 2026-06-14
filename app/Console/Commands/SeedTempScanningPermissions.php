<?php

namespace App\Console\Commands;

use App\Models\PermissionGroup;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedTempScanningPermissions extends Command
{
    protected $signature   = 'permissions:seed-temp-scanning';
    protected $description = 'Add the Temp Scanning permission group and its permissions (idempotent)';

    private const GROUP = 'Temp Scanning';

    private const PERMISSIONS = [
        'temp-scanning.view',    // view / upload temp scans (index)
        'temp-scanning.create',  // store main file + supporting files
        'temp-scanning.delete',  // destroy scan / support file
    ];

    public function handle(): int
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permission group ───────────────────────────────────────────────
        if (PermissionGroup::where('name', self::GROUP)->exists()) {
            $this->line('  Group already exists — skipping: <comment>' . self::GROUP . '</comment>');
        } else {
            PermissionGroup::create(['name' => self::GROUP]);
            $this->info('  Created group: ' . self::GROUP);
        }

        // ── Permissions ────────────────────────────────────────────────────
        $created = 0;
        foreach (self::PERMISSIONS as $name) {
            $perm = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['group' => self::GROUP]
            );
            if ($perm->wasRecentlyCreated) {
                $this->info('  Created permission: ' . $name);
                $created++;
            } else {
                $this->line('  Already exists:    ' . $name);
            }
        }

        // ── Super Admin ────────────────────────────────────────────────────
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $newPerms = Permission::whereIn('name', self::PERMISSIONS)->get();
            $superAdmin->givePermissionTo($newPerms);
            $this->info('  Super Admin granted all Temp Scanning permissions.');
        } else {
            $this->warn('  Super Admin role not found — skipping role assignment.');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->info('Done. ' . $created . ' new permission(s) created.');
        return self::SUCCESS;
    }
}
