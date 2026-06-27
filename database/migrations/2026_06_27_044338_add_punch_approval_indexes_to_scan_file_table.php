<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Optimizes queries for 10 lakh+ records in punch approval module
     */
    public function up(): void
    {
        // Drop old indexes that might conflict
        $this->dropIndexIfExists('idx_punch_date');
        $this->dropIndexIfExists('idx_approve_date');
        $this->dropIndexIfExists('idx_reject_date');

        // Create optimized composite covering index for pending tab
        // This index covers the WHERE + ORDER BY + SELECT for pending queries
        $this->createIndexIfNotExists(
            'idx_pending_covering',
            'CREATE INDEX idx_pending_covering ON scan_file (Group_Id, Is_Deleted, File_Punched, File_Approved, Is_Rejected, Punch_Date DESC) USING BTREE'
        );

        // Create optimized composite covering index for approved tab
        $this->createIndexIfNotExists(
            'idx_approved_covering',
            'CREATE INDEX idx_approved_covering ON scan_file (Group_Id, Is_Deleted, File_Punched, File_Approved, Punch_Date DESC) USING BTREE'
        );

        // Create optimized composite covering index for rejected tab
        $this->createIndexIfNotExists(
            'idx_rejected_covering',
            'CREATE INDEX idx_rejected_covering ON scan_file (Group_Id, Is_Deleted, File_Punched, Is_Rejected, Punch_Date DESC) USING BTREE'
        );

        // Keep approval filters index
        $this->createIndexIfNotExists(
            'idx_approval_filters',
            'CREATE INDEX idx_approval_filters ON scan_file (Group_Id, year_id, Location, DocType_Id) USING BTREE'
        );

        // Add index on foreign key columns for JOINs
        $this->createIndexIfNotExists(
            'idx_punch_by',
            'CREATE INDEX idx_punch_by ON scan_file (Punch_By) USING BTREE'
        );

        $this->createIndexIfNotExists(
            'idx_approve_by',
            'CREATE INDEX idx_approve_by ON scan_file (Approve_By) USING BTREE'
        );
    }

    /**
     * Helper method to safely create index only if it doesn't exist
     */
    private function createIndexIfNotExists(string $indexName, string $createSql): void
    {
        try {
            $exists = DB::select("SHOW INDEX FROM scan_file WHERE Key_name = ?", [$indexName]);
            if (empty($exists)) {
                DB::statement($createSql);
            }
        } catch (\Exception $e) {
            // Log but don't fail if index already exists
            \Log::warning("Index creation skipped: {$indexName} - " . $e->getMessage());
        }
    }

    /**
     * Helper method to safely drop index if it exists
     */
    private function dropIndexIfExists(string $indexName): void
    {
        try {
            $exists = DB::select("SHOW INDEX FROM scan_file WHERE Key_name = ?", [$indexName]);
            if (!empty($exists)) {
                DB::statement("DROP INDEX {$indexName} ON scan_file");
            }
        } catch (\Exception $e) {
            // Index might not exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'idx_punch_approval_queue',
            'idx_pending_covering',
            'idx_approved_covering',
            'idx_rejected_covering',
            'idx_approval_filters',
            'idx_punch_by',
            'idx_approve_by',
        ];

        foreach ($indexes as $indexName) {
            try {
                DB::statement("DROP INDEX {$indexName} ON scan_file");
            } catch (\Exception $e) {
                // Index might not exist, continue
            }
        }
    }
};
