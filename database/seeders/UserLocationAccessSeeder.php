<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserLocationAccessSeeder extends Seeder
{
    /**
     * Migrate location access from scan_users.location_id (comma-separated)
     * into the normalised user_location_access table.
     *
     * Logic:
     *  - If a user has a non-empty location_id string  → only those IDs get has_access = true,
     *    all others get has_access = false.
     *  - If a user has no location_id (NULL / empty)   → skip; the controller already defaults
     *    to "all open" when no explicit rows exist.
     */
    public function run(): void
    {
        // Get all valid location IDs from master_work_location
        $validLocationIds = DB::table('master_work_location')
            ->where('status', 'A')
            ->where('is_deleted', 'N')
            ->pluck('location_id')
            ->map(fn($id) => (int) $id)
            ->all();

        // Get all scan_users that have location_id set
        $scanUsers = DB::table('scan_users')
            ->whereNotNull('location_id')
            ->where('location_id', '!=', '')
            ->get(['user_id', 'location_id']);

        $now  = now();
        $rows = [];

        foreach ($scanUsers as $su) {
            $userId = (int) $su->user_id;

            // Verify this user exists in the users table
            $exists = DB::table('users')->where('id', $userId)->exists();
            if (! $exists) {
                continue;
            }

            // Parse the allowed location IDs
            $allowedIds = collect(explode(',', $su->location_id))
                ->map(fn($v) => (int) trim($v))
                ->filter()
                ->unique()
                ->all();

            // Delete any previous rows for this user (idempotent re-run)
            DB::table('user_location_access')->where('user_id', $userId)->delete();

            // Insert a row for every active location
            foreach ($validLocationIds as $locationId) {
                $rows[] = [
                    'user_id'     => $userId,
                    'location_id' => $locationId,
                    'has_access'  => in_array($locationId, $allowedIds, true) ? 1 : 0,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            // Flush in batches to avoid huge single inserts
            if (count($rows) >= 500) {
                DB::table('user_location_access')->insert($rows);
                $rows = [];
            }
        }

        if (! empty($rows)) {
            DB::table('user_location_access')->insert($rows);
        }

        $total = DB::table('user_location_access')->count();
        $this->command->info("Seeded {$total} user_location_access rows for " . count($scanUsers) . " users.");
    }
}
