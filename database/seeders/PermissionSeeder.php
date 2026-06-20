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
        // ── 1. Reset ───────────────────────────────────────────────────────
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        \DB::table('role_has_permissions')->truncate();
        \DB::table('model_has_permissions')->truncate();

        Permission::query()->delete();
        PermissionGroup::query()->delete();

        // ── 2. Permission groups & names ───────────────────────────────────
        //
        // Names are derived by CheckPermission middleware logic:
        //   route "settings.company.store" → strip "settings." → "company.store"
        //   → resource = "company", action "store" → "create"
        //   → permission = "company.create"
        //
        $groups = [

            // ── Dashboard ─────────────────────────────────────────────────
            'Dashboard' => [
                'dashboard.view',                       // GET /dashboard
            ],

            // ── Page Builder (under /master/page-builder) ─────────────────
            'Page Builder' => [
                'page-builder.view',                    // index, data, show, fields, preview, shares
                'page-builder.create',                  // create, store
                'page-builder.edit',                    // edit, update
                'page-builder.delete',                  // destroy, bulk-destroy
                'page-builder.generate',                // POST .../generate
                'page-builder.fields.manage',           // fields store/update/reorder/destroy
            ],

            // ── Import Data ───────────────────────────────────────────────
            'Import Data' => [
                'import.view',                          // index, jobs list, show, preview
                'import.create',                        // upload, start
                'import.delete',                        // destroy job
                'import.api-connections.view',          // api-connections list
                'import.api-connections.create',        // store api-connection
                'import.api-connections.delete',        // destroy api-connection
            ],

            // ── Document AI Predictor ─────────────────────────────────────
            // All document-ai.* routes are in ALWAYS_ALLOW — no granular
            // permissions enforced by middleware. Add a single group so
            // roles can still be granted/denied at the menu level.
            'AI Predictor' => [
                'document-ai.view',                     // playground, logs, analytics, dept-rules
                'document-ai.predict',                  // POST predict / save-classification
                'document-ai.training.manage',          // training store / update / delete
                'document-ai.dept-rules.manage',        // dept-rules store / update / delete
                'document-ai.settings.manage',          // settings, type toggle
            ],

            // ── Profile ───────────────────────────────────────────────────
            'Profile' => [
                'profile.view',                         // GET /profile
                'profile.update',                       // POST /profile/info
                'profile.password.change',              // POST /profile/password
            ],

            // ── Settings (General) ────────────────────────────────────────
            'Settings' => [
                'settings.view',                        // GET /settings
                'settings.update',                      // POST /settings (always-allowed, but good to have)
            ],

            // ── Company ───────────────────────────────────────────────────
            'Company' => [
                'company.view',                         // index, show
                'company.create',                       // store
                'company.edit',                         // update
                'company.delete',                       // destroy
                'company.set-default',                  // POST .../default
            ],

            // ── Financial Year ────────────────────────────────────────────
            'Financial Year' => [
                'financial-year.view',                  // index, show
                'financial-year.create',                // store
                'financial-year.edit',                  // update
                'financial-year.delete',                // destroy
                'financial-year.set-current',           // POST .../current
            ],

            // ── Numbering ─────────────────────────────────────────────────
            'Numbering' => [
                'numbering.view',                       // index, show
                'numbering.edit',                       // update
            ],

            // ── Document Types ────────────────────────────────────────────
            'Document Types' => [
                'document-types.view',                  // index, data, show
                'document-types.create',                // store
                'document-types.edit',                  // update
                'document-types.delete',                // destroy
            ],

            // ── Ext Master — API Control ──────────────────────────────────
            'Ext API Control' => [
                'ext-api-control.view',                 // index, data, show
                'ext-api-control.create',               // store
                'ext-api-control.edit',                 // update
                'ext-api-control.delete',               // destroy
            ],

            // ── Ext Master — Field Mappings ───────────────────────────────
            'Ext Field Mappings' => [
                'ext-field-mappings.view',              // index, data, show
                'ext-field-mappings.create',            // store
                'ext-field-mappings.edit',              // update
                'ext-field-mappings.delete',            // destroy
            ],

            // ── Bill Date Sync ────────────────────────────────────────────
            'Bill Date Sync' => [
                'bill-date-sync.view',                  // GET /settings/bill-date-sync
                // POST .../process → action "process" not in ACTION_MAP → middleware passes through
            ],

            // ── Users ─────────────────────────────────────────────────────
            'Users' => [
                'users.view',                           // index, data, show
                'users.create',                         // store
                'users.edit',                           // update
                'users.delete',                         // destroy
                'users.roles.manage',                   // GET/PUT .../roles
                'users.permissions.manage',             // GET/PUT .../permissions
            ],

            // ── Roles ─────────────────────────────────────────────────────
            'Roles' => [
                'roles.view',                           // index, data, show
                'roles.create',                         // store
                'roles.edit',                           // update
                'roles.delete',                         // destroy
                'roles.permissions.manage',             // GET/PUT .../permissions
            ],

            // ── Permissions ───────────────────────────────────────────────
            'Permissions' => [
                'permissions.view',                     // index, data, show
                'permissions.create',                   // store
                'permissions.edit',                     // update
                'permissions.delete',                   // destroy
                'permission-groups.manage',             // store/destroy permission groups
            ],

            // ── Temp Scanning (Workflow) ───────────────────────────────────
            // All workflow.temp-scan.* routes are in ALWAYS_ALLOW — access is
            // gated by role ('Temp Scanner', 'Bill Approval', etc.) at the
            // menu/nav level, not by granular permissions. This group exists
            // purely so the Permissions panel UI can display it.
            'Temp Scanning' => [
                'temp-scanning.view',                   // index — view/upload temp scans
                'temp-scanning.create',                 // store main file + supporting files
                'temp-scanning.delete',                 // destroy scan / support file
                'temp-scanning.final-submit',           // POST .../final-submit
                'temp-scanning.resubmit',               // POST .../resubmit
                'temp-scanning.export',                 // export excel / pdf
            ],

            // ── Super Scanner (Workflow) ──────────────────────────────────
            // All workflow.super-scanner.* routes are in ALWAYS_ALLOW — gated
            // by 'Super Scanner' role at menu level.
            'Super Scanner' => [
                'super-scanner.view',                   // index — company-wise summary
                'super-scanner.scan',                   // company scan / verify / supporting / final-submit / destroy
                'super-scanner.export',                 // export excel / pdf
            ],

            // ── Direct Scanning (Workflow) ──────────────────────────────────
            // All workflow.direct-scan.* routes are in ALWAYS_ALLOW — access is
            // gated by role ('Direct Scanning', 'Bill Approval', etc.) at the
            // menu/nav level, not by granular permissions. This group exists
            // purely so the Permissions panel UI can display it.
            'Direct Scanning' => [
                'direct-scanning.view',                 // index — view/upload direct scans
                'direct-scanning.create',               // store main file + supporting files
                'direct-scanning.delete',               // destroy scan / support file
                'direct-scanning.final-submit',         // final submit action
                'direct-scanning.resubmit',             // resubmit after rejection
                'direct-scanning.export',               // export excel / pdf
            ],

            // ── PDF Compressor (Tools) ────────────────────────────────────
            'PDF Compressor' => [
                'pdf-compressor.view',                  // playground page + history list
                'pdf-compressor.compress',              // upload + compress AJAX action
                'pdf-compressor.download',              // download compressed file
                'pdf-compressor.delete',                // delete job record + temp files
            ],

        ];

        // ── 3. Create groups & permissions ────────────────────────────────
        foreach ($groups as $groupName => $permNames) {
            PermissionGroup::create(['name' => $groupName]);

            foreach ($permNames as $permName) {
                Permission::create([
                    'name'       => $permName,
                    'guard_name' => 'web',
                    'group'      => $groupName,
                ]);
            }
        }

        // ── 4. Assign ALL permissions to Super Admin ───────────────────────
        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['is_active' => true]
        );
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('✓ Permissions seeded: ' . Permission::count() . ' permissions in ' . PermissionGroup::count() . ' groups.');
        $this->command->info('✓ Super Admin role assigned all permissions.');
    }
}
