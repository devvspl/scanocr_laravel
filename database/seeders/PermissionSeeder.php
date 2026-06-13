<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Wipe existing permissions & groups ──────────────────────────
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Detach all permissions from roles and users first
        \DB::table('role_has_permissions')->truncate();
        \DB::table('model_has_permissions')->truncate();

        Permission::query()->delete();
        PermissionGroup::query()->delete();

        // ── 2. Define permissions by group ────────────────────────────────
        $groups = [

            'Sales Invoices' => [
                'sales-invoices.view',
                'sales-invoices.create',
                'sales-invoices.edit',
                'sales-invoices.delete',
                'sales-invoices.submit',
                'sales-invoices.approve',
                'sales-invoices.reject',
                'sales-invoices.cancel',
            ],

            'Proforma Invoices' => [
                'sales-proforma.view',
                'sales-proforma.create',
                'sales-proforma.edit',
                'sales-proforma.delete',
                'sales-proforma.submit',
                'sales-proforma.approve',
                'sales-proforma.reject',
                'sales-proforma.cancel',
            ],

            'Delivery Notes' => [
                'sales-delivery.view',
                'sales-delivery.create',
                'sales-delivery.edit',
                'sales-delivery.delete',
                'sales-delivery.submit',
                'sales-delivery.approve',
                'sales-delivery.reject',
                'sales-delivery.cancel',
            ],

            'Credit Notes' => [
                'sales-credit-notes.view',
                'sales-credit-notes.create',
                'sales-credit-notes.edit',
                'sales-credit-notes.delete',
                'sales-credit-notes.submit',
                'sales-credit-notes.approve',
                'sales-credit-notes.reject',
                'sales-credit-notes.cancel',
            ],

            'Sales Receipts' => [
                'sales-receipts.view',
                'sales-receipts.create',
                'sales-receipts.edit',
                'sales-receipts.delete',
                'sales-receipts.submit',
                'sales-receipts.approve',
                'sales-receipts.reject',
                'sales-receipts.cancel',
            ],

            'Sales Reports' => [
                'sales-register.view',
                'sales-outstanding.view',
            ],

            'Dashboard' => [
                'dashboard.view',
            ],

            'Import Data' => [
                'import.view',
                'import.create',
                'import.delete',
                'import.api-connections.view',
                'import.api-connections.create',
                'import.api-connections.delete',
            ],

            'Page Builder' => [
                'page-builder.view',
                'page-builder.create',
                'page-builder.edit',
                'page-builder.delete',
                'page-builder.generate',
                'page-builder.fields.manage',
            ],

            'Account Groups' => [
                'account-groups.view',
                'account-groups.create',
                'account-groups.edit',
                'account-groups.delete',
            ],

            'Chart of Accounts' => [
                'accounts.view',
                'accounts.create',
                'accounts.edit',
                'accounts.delete',
            ],

            'Customers' => [
                'customers.view',
                'customers.create',
                'customers.edit',
                'customers.delete',
            ],

            'Vendors' => [
                'vendors.view',
                'vendors.create',
                'vendors.edit',
                'vendors.delete',
            ],

            'Products' => [
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
            ],

            'Item Groups' => [
                'item-groups.view',
                'item-groups.create',
                'item-groups.edit',
                'item-groups.delete',
            ],

            'Units of Measure' => [
                'units.view',
                'units.create',
                'units.edit',
                'units.delete',
            ],

            'Tax Rates' => [
                'taxes.view',
                'taxes.create',
                'taxes.edit',
                'taxes.delete',
            ],

            'HSN / SAC Codes' => [
                'hsn.view',
                'hsn.create',
                'hsn.edit',
                'hsn.delete',
            ],

            'Profile' => [
                'profile.view',
                'profile.update',
                'profile.password.change',
            ],

            'Settings' => [
                'settings.view',
                'settings.update',
            ],

            'Company' => [
                'company.view',
                'company.create',
                'company.edit',
                'company.delete',
                'company.set-default',
            ],

            'Financial Year' => [
                'financial-year.view',
                'financial-year.create',
                'financial-year.edit',
                'financial-year.delete',
                'financial-year.set-current',
            ],

            'Numbering' => [
                'numbering.view',
                'numbering.edit',
            ],

            'Document' => [
                'document-types.view',
                'document-types.create',
                'document-types.edit',
                'document-types.delete',
            ],

            'Users' => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'users.roles.manage',
                'users.permissions.manage',
            ],

            'Roles' => [
                'roles.view',
                'roles.create',
                'roles.edit',
                'roles.delete',
                'roles.permissions.manage',
            ],

            'Permissions' => [
                'permissions.view',
                'permissions.create',
                'permissions.edit',
                'permissions.delete',
                'permission-groups.manage',
            ],

            'Workflow Designer' => [
                'workflow.view',
                'workflow.create',
                'workflow.edit',
                'workflow.delete',
            ],

        ];

        // ── 3. Create groups & permissions ────────────────────────────────
        foreach ($groups as $groupName => $permNames) {
            $group = PermissionGroup::create(['name' => $groupName]);

            foreach ($permNames as $permName) {
                Permission::create([
                    'name'       => $permName,
                    'guard_name' => 'web',
                    'group'      => $groupName,
                ]);
            }
        }

        // ── 4. Assign ALL permissions to Super Admin role ─────────────────
        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['is_active' => true]
        );
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('✓ Permissions seeded: ' . Permission::count() . ' permissions in ' . PermissionGroup::count() . ' groups.');
        $this->command->info('✓ Super Admin role assigned all permissions.');
    }
}
