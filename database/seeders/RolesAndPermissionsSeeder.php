<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage user roles',
            'manage user permissions',
            
            // Role Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'manage role permissions',
            
            // Permission Management
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            
            // Master Data
            'view accounts',
            'create accounts',
            'edit accounts',
            'delete accounts',
            
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            
            'view vendors',
            'create vendors',
            'edit vendors',
            'delete vendors',
            
            'view products',
            'create products',
            'edit products',
            'delete products',
            
            // Settings
            'view settings',
            'edit settings',
            'view company info',
            'edit company info',
            'view financial year',
            'edit financial year',
            'view numbering',
            'edit numbering',
            'view document types',
            'edit document types',
            
            // Reports
            'view reports',
            'export reports',
            
            // Dashboard
            'view dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        $adminRole = Role::create(['name' => 'Admin']);
        $adminRole->givePermissionTo([
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage user roles',
            'view roles',
            'view accounts',
            'create accounts',
            'edit accounts',
            'delete accounts',
            'view customers',
            'create customers',
            'edit customers',
            'delete customers',
            'view vendors',
            'create vendors',
            'edit vendors',
            'delete vendors',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view settings',
            'edit settings',
            'view reports',
            'export reports',
            'view dashboard',
        ]);

        $managerRole = Role::create(['name' => 'Manager']);
        $managerRole->givePermissionTo([
            'view users',
            'view accounts',
            'create accounts',
            'edit accounts',
            'view customers',
            'create customers',
            'edit customers',
            'view vendors',
            'create vendors',
            'edit vendors',
            'view products',
            'create products',
            'edit products',
            'view reports',
            'export reports',
            'view dashboard',
        ]);

        $userRole = Role::create(['name' => 'User']);
        $userRole->givePermissionTo([
            'view accounts',
            'view customers',
            'view vendors',
            'view products',
            'view reports',
            'view dashboard',
        ]);

        $viewerRole = Role::create(['name' => 'Viewer']);
        $viewerRole->givePermissionTo([
            'view accounts',
            'view customers',
            'view vendors',
            'view products',
            'view dashboard',
        ]);
    }
}
