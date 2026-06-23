<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Assigns the correct permissions to each role.
 * Run AFTER PermissionSeeder. Safe to re-run; syncPermissions() is idempotent.
 */
class RolePermissionSeeder extends Seeder
{
    private array $rolePermissions = [

        'Temp Scanning' => [
            'dashboard.view',
            'profile.view', 'profile.update', 'profile.password.change',
            'temp-scanning.view', 'temp-scanning.create', 'temp-scanning.delete',
            'temp-scanning.final-submit', 'temp-scanning.resubmit',
        ],

        'Direct Scanning' => [
            'dashboard.view',
            'profile.view', 'profile.update', 'profile.password.change',
            'direct-scanning.view', 'direct-scanning.create', 'direct-scanning.delete',
            'direct-scanning.final-submit', 'direct-scanning.resubmit', 'direct-scanning.export',
        ],

        'Super Scanner' => [
            'dashboard.view',
            'profile.view', 'profile.update', 'profile.password.change',
            'temp-scanning.view', 'temp-scanning.create', 'temp-scanning.delete',
            'temp-scanning.final-submit', 'temp-scanning.resubmit', 'temp-scanning.export',
            'direct-scanning.view', 'direct-scanning.create', 'direct-scanning.delete',
            'direct-scanning.final-submit', 'direct-scanning.resubmit', 'direct-scanning.export',
            'super-scanner.view', 'super-scanner.scan', 'super-scanner.export',
        ],

        'Bill Approval' => [
            'dashboard.view',
            'profile.view', 'profile.update', 'profile.password.change',
            'temp-scanning.view',
            'direct-scanning.view',
            'bill-approval.view', 'bill-approval.approve', 'bill-approval.reject',
            'bill-approval.manage-reasons',
        ],

        'Classification' => [
            'dashboard.view',
            'profile.view', 'profile.update', 'profile.password.change',
            'temp-scanning.view', 'direct-scanning.view',
            'document-ai.view',
            'classification.view', 'classification.classify',
        ],

        'Data Punching' => [
            'dashboard.view',
            'profile.view', 'profile.update', 'profile.password.change',
            'temp-scanning.view', 'direct-scanning.view',
            'punching.view', 'punching.punch',
        ],

        'Punch Approval' => [
            'dashboard.view',
            'profile.view', 'profile.update', 'profile.password.change',
            'temp-scanning.view', 'direct-scanning.view',
        ],
    ];

    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $existing = Permission::pluck('name')->toArray();

        foreach ($this->rolePermissions as $roleName => $permissionNames) {
            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                $this->command->warn("  Role [{$roleName}] not found — skipping.");
                continue;
            }

            $missing = array_diff($permissionNames, $existing);
            if (! empty($missing)) {
                $this->command->warn("  Role [{$roleName}]: unknown permissions skipped: " . implode(', ', $missing));
            }

            $valid = array_intersect($permissionNames, $existing);
            $role->syncPermissions($valid);
            $this->command->info("  Role [{$roleName}]: synced " . count($valid) . " permission(s).");
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('Permission cache cleared.');
    }
}
