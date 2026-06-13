<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // Wipe any previously seeded / demo data
        DB::table('companies')->truncate();

        $rows = DB::select("SELECT group_id, group_name FROM master_group WHERE is_deleted = 'N' ORDER BY group_id");

        $now = now();

        foreach ($rows as $row) {
            DB::table('companies')->insert([
                'id'           => $row->group_id,
                'name'         => $row->group_name,
                'display_name' => $row->group_name,
                'is_active'    => true,
                'is_default'   => false,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        // Set first company as default
        DB::table('companies')->where('id', DB::table('companies')->min('id'))->update(['is_default' => true]);

        // Bump AUTO_INCREMENT past the last inserted id
        $maxId = DB::table('companies')->max('id') ?? 0;
        DB::statement("ALTER TABLE companies AUTO_INCREMENT = " . ($maxId + 1));

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        DB::table('companies')->truncate();
    }
};
