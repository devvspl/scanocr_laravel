<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkflowPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define workflow permissions
        $groupName = 'Workflow Designer';
        $permNames = [
            'workflow.view',
            'workflow.create',
            'workflow.edit',
            'workflow.delete',
            'workflow.duplicate',
            'workflow.activate',
            'workflow.designer.access',
            'workflow.stages.manage',
            'workflow.actions.manage',
            'workflow.routing.manage',
            'workflow.log.view',
        ];

        // Create permission group if it doesn't exist
        $group = PermissionGroup::firstOrCreate(['name' => $groupName]);

        // Create permissions
        foreach ($permNames as $permName) {
            Permission::firstOrCreate(
                ['name' => $permName, 'guard_name' => 'web'],
                ['group' => $groupName]
            );
        }

        // Assign all workflow permissions to Super Admin role
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $workflowPermissions = Permission::whereIn('name', $permNames)->get();
            $superAdmin->givePermissionTo($workflowPermissions);
        }

        $this->command->info('✓ Workflow permissions seeded: ' . count($permNames) . ' permissions.');
        $this->command->info('✓ Super Admin role assigned workflow permissions.');
    }
}
