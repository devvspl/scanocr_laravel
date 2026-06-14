<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Syncs legacy scan_users → users.
 *
 * Rules
 * ─────
 * • Only runs if the scan_users table exists.
 * • Preserves the original user_id as `id` in the users table.
 * • Skips rows whose id already exists in users (idempotent).
 * • username already contains '@'  → use it directly as email.
 * • username has no '@'            → append @migrated.local.
 * • Duplicate emails get a numeric suffix before @:
 *     admin@acme.com → admin1@acme.com → admin2@acme.com …
 * • Maps scan_users.status 'A'/'D' → users.is_active 1/0.
 * • Password is carried over as-is (already hashed in the old system).
 * • location_id: first numeric value from the comma-separated location_id string.
 * • Resets AUTO_INCREMENT to MAX(id)+1 afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scan_users')) {
            return;
        }

        $oldUsers = DB::table('scan_users')->get();

        if ($oldUsers->isEmpty()) {
            return;
        }

        // Track what already exists in the target table
        $existingIds    = DB::table('users')->pluck('id')->flip()->toArray();
        $existingEmails = DB::table('users')->pluck('email')->flip()->toArray();

        $rows = [];
        $now  = now()->toDateTimeString();

        // Pre-compute one bcrypt hash to reuse for all rows (avoids 248× bcrypt cost).
        // Old passwords were plain MD5 — they cannot be used with Laravel's bcrypt verifier.
        // All migrated users will have 'password' as their initial password and should reset it.
        $defaultPassword = bcrypt('password');

        foreach ($oldUsers as $old) {
            // Skip if this ID is already in users
            if (array_key_exists($old->user_id, $existingIds)) {
                continue;
            }

            // ── Build email ───────────────────────────────────────────────
            // If username contains '@', use it as-is; otherwise add @migrated.local
            if (str_contains($old->username, '@')) {
                $baseEmail = strtolower(trim($old->username));
            } else {
                $baseEmail = strtolower(str_replace(' ', '', $old->username)) . '@migrated.local';
            }

            // Deduplicate: admin@acme.com → admin1@acme.com → admin2@acme.com
            $email  = $baseEmail;
            $suffix = 1;
            while (array_key_exists($email, $existingEmails)) {
                [$local, $domain] = explode('@', $baseEmail, 2);
                $email = $local . $suffix . '@' . $domain;
                $suffix++;
            }
            // ─────────────────────────────────────────────────────────────

            // ── Build location_id ─────────────────────────────────────────
            // scan_users.location_id is a comma-separated string; take the first valid int
            $rawLocation = trim($old->location_id ?? '');
            $locationId  = null;
            if ($rawLocation !== '') {
                $first = (int) explode(',', $rawLocation)[0];
                if ($first > 0) {
                    $locationId = $first;
                }
            }
            // ─────────────────────────────────────────────────────────────

            $rows[] = [
                'id'                => $old->user_id,
                'parent_id'         => null,
                'name'              => trim($old->first_name . ' ' . $old->last_name),
                'email'             => $email,
                'phone'             => null,
                'designation'       => null,
                'department'        => ($old->department_id !== '0') ? $old->department_id : null,
                'location_id'       => $locationId,
                'created_by'        => null,
                'email_verified_at' => $old->created_at ?? $now,
                'is_active'         => (($old->status ?? 'A') === 'A') ? 1 : 0,
                'password'          => $defaultPassword, // old MD5 replaced — default is 'password', user should reset
                'remember_token'    => null,
                'created_at'        => $old->created_at ?? $now,
                'updated_at'        => $old->updated_at ?? $now,
            ];

            // Mark as used so subsequent rows deduplicate against it
            $existingIds[$old->user_id] = true;
            $existingEmails[$email]     = true;
        }

        if (empty($rows)) {
            return;
        }

        // Insert in chunks — supplying explicit id bypasses AUTO_INCREMENT
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        // Reset AUTO_INCREMENT to MAX(id)+1 so future inserts don't collide
        $maxId = DB::table('users')->max('id');
        DB::statement('ALTER TABLE users AUTO_INCREMENT = ' . ($maxId + 1));
    }

    public function down(): void
    {
        // Cannot safely reverse — would delete real production users. No-op.
    }
};
