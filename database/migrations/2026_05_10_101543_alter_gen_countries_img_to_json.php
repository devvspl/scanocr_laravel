<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Step 1: Change column to TEXT NULL first (bypasses check constraint & NOT NULL)
        DB::statement('ALTER TABLE `gen_countries` MODIFY `img` TEXT NULL');

        // Step 2: Migrate existing string paths → JSON arrays
        DB::table('gen_countries')->whereNotNull('img')->where('img', '!=', '')->get()->each(function ($row) {
            $decoded = json_decode($row->img, true);
            if (!is_array($decoded)) {
                DB::table('gen_countries')->where('id', $row->id)->update([
                    'img' => json_encode([$row->img]),
                ]);
            }
        });

        // Step 3: Null out empty strings
        DB::table('gen_countries')->where('img', '')->orWhereNull('img')->update(['img' => null]);

        // Step 4: Change to proper JSON column
        DB::statement('ALTER TABLE `gen_countries` MODIFY `img` JSON NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `gen_countries` MODIFY `img` VARCHAR(255) NULL');
    }
};
