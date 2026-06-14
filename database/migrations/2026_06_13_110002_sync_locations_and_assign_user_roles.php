<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Two-part migration:
 *
 * PART A — Sync master_work_location → locations
 * ──────────────────────────────────────────────
 * • Only runs if master_work_location table exists.
 * • Preserves original location_id as `id` in the locations table.
 * • Skips IDs already present (idempotent).
 * • Maps status 'A'/'D' → is_active 1/0.
 * • Resets AUTO_INCREMENT afterwards.
 *
 * PART B — Assign roles + location_id to migrated users
 * ──────────────────────────────────────────────────────
 * • Old role enum → new Spatie role name:
 *     super_admin    → Super Admin
 *     admin          → Super Admin
 *     super_approver → Punch Approval
 *     super_scan     → Temp Scanning
 *     bill_approver  → Bill Approval
 *     user / blank   → (no role assigned)
 * • location_id: takes the FIRST numeric value from the comma-separated
 *   location_id string in scan_users.
 * • Skips users already having model_has_roles entries (idempotent).
 */
return new class extends Migration
{
    // Old scan role → new Spatie role name
    private const ROLE_MAP = [
        'super_admin'    => 'Super Admin',
        'admin'          => 'Super Admin',
        'super_approver' => 'Punch Approval',
        'super_scan'     => 'Temp Scanning',
        'bill_approver'  => 'Bill Approval',
        // 'user' and '' → no role
    ];

    // ──────────────────────────────────────────────────────────────────────
    public function up(): void
    {
        $this->syncLocations();
        $this->assignRolesAndLocations();
    }

    // ──────────────────────────────────────────────────────────────────────
    private function syncLocations(): void
    {
        if (! Schema::hasTable('master_work_location')) {
            return;
        }

        $oldLocations  = DB::table('master_work_location')->get();
        $existingIds   = DB::table('locations')->pluck('id')->flip()->toArray();
        $now           = now()->toDateTimeString();
        $rows          = [];

        foreach ($oldLocations as $loc) {
            if (array_key_exists($loc->location_id, $existingIds)) {
                continue; // already synced
            }

            $rows[] = [
                'id'         => $loc->location_id,
                'name'       => $loc->location_name,
                'code'       => $loc->location_code !== '0' ? $loc->location_code : null,
                'state_name' => null,
                'state_code' => null,
                'is_group'   => 0,
                'is_active'  => $loc->status === 'A' ? 1 : 0,
                'created_at' => $loc->created_at ?? $now,
                'updated_at' => $loc->updated_at ?? $now,
            ];

            $existingIds[$loc->location_id] = true;
        }

        if (! empty($rows)) {
            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table('locations')->insert($chunk);
            }

            $maxId = DB::table('locations')->max('id');
            DB::statement('ALTER TABLE locations AUTO_INCREMENT = ' . ($maxId + 1));
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    private function assignRolesAndLocations(): void
    {
        if (! Schema::hasTable('scan_users')) {
            return;
        }

        // Load Spatie roles into a name → id map
        $roleMap = DB::table('roles')
            ->pluck('id', 'name')
            ->toArray();

        // Users that already have at least one role assigned — skip them
        $alreadyAssigned = DB::table('model_has_roles')
            ->where('model_type', 'App\\Models\\User')
            ->pluck('model_id')
            ->flip()
            ->toArray();

        $oldUsers = DB::table('scan_users')
            ->select('user_id', 'role', 'location_id')
            ->get();

        $roleInserts     = [];
        $locationUpdates = []; // user_id => first_location_id

        foreach ($oldUsers as $old) {
            // ── Location: take first numeric value from comma-separated string ──
            $rawLocation = trim($old->location_id ?? '');
            if ($rawLocation !== '') {
                $firstId = (int) explode(',', $rawLocation)[0];
                if ($firstId > 0) {
                    $locationUpdates[$old->user_id] = $firstId;
                }
            }

            // ── Role assignment ───────────────────────────────────────────
            if (array_key_exists($old->user_id, $alreadyAssigned)) {
                continue; // already has roles — don't override
            }

            $newRoleName = self::ROLE_MAP[$old->role ?? ''] ?? null;
            if ($newRoleName === null) {
                continue; // 'user' or blank → no role
            }

            $roleId = $roleMap[$newRoleName] ?? null;
            if ($roleId === null) {
                continue; // role doesn't exist in new system
            }

            $roleInserts[] = [
                'role_id'    => $roleId,
                'model_type' => 'App\\Models\\User',
                'model_id'   => $old->user_id,
            ];
        }

        // Bulk-insert role assignments
        if (! empty($roleInserts)) {
            foreach (array_chunk($roleInserts, 100) as $chunk) {
                DB::table('model_has_roles')->insertOrIgnore($chunk);
            }
        }

        // Update location_id on users table
        foreach ($locationUpdates as $userId => $locationId) {
            DB::table('users')
                ->where('id', $userId)
                ->update(['location_id' => $locationId]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    public function down(): void
    {
        // Remove assigned roles from migrated scan_users
        if (Schema::hasTable('scan_users') && Schema::hasTable('model_has_roles')) {
            $oldIds = DB::table('scan_users')->pluck('user_id')->toArray();
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->whereIn('model_id', $oldIds)
                ->delete();
        }

        // Clear location_id on those users
        if (Schema::hasTable('scan_users') && Schema::hasTable('users')) {
            $oldIds = DB::table('scan_users')->pluck('user_id')->toArray();
            DB::table('users')->whereIn('id', $oldIds)->update(['location_id' => null]);
        }

        // Remove synced locations (only those that came from master_work_location)
        if (Schema::hasTable('master_work_location') && Schema::hasTable('locations')) {
            $oldLocationIds = DB::table('master_work_location')->pluck('location_id')->toArray();
            DB::table('locations')->whereIn('id', $oldLocationIds)->delete();
        }
    }
};
