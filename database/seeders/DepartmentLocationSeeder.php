<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentLocationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDepartments();
        $this->seedLocations();
    }

    private function seedDepartments(): void
    {
        if (DB::table('departments')->count() > 0) {
            return;
        }

        $jsonPath = public_path('core_department.json');
        if (!file_exists($jsonPath)) {
            $this->command->warn('core_department.json not found in public/');
            return;
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        $data = collect($json)->firstWhere('type', 'table')['data'] ?? [];

        $inserted = 0;
        foreach ($data as $row) {
            // Skip duplicates by code
            $exists = DB::table('departments')
                ->where('department_code', $row['department_code'])
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('departments')->insert([
                'department_name' => $row['department_name'],
                'department_code' => $row['department_code'],
                'numeric_code'    => $row['numeric_code'] ?? null,
                'effective_date'  => $row['effective_date'] ?? null,
                'is_active'       => ($row['is_active'] ?? '1') === '1',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $inserted++;
        }

        $this->command->info("Seeded {$inserted} departments.");
    }

    private function seedLocations(): void
    {
        if (DB::table('locations')->count() > 0) {
            return;
        }

        $jsonPath = public_path('location_master.json');
        if (!file_exists($jsonPath)) {
            $this->command->warn('location_master.json not found in public/');
            return;
        }

        $json = json_decode(file_get_contents($jsonPath), true);
        $data = collect($json)->firstWhere('type', 'table')['data'] ?? [];

        foreach ($data as $row) {
            // Skip "All Location" and "DO NOT USE" entries
            if (in_array($row['sName'], ['All Location', 'DO NOT USE'])) {
                continue;
            }

            $isGroup = strtoupper($row['bGroup'] ?? 'FALSE') === 'TRUE';

            DB::table('locations')->insert([
                'name'       => $row['sName'],
                'code'       => $row['sCode'] ?? null,
                'state_name' => $row['parent_name'] ?? null,
                'state_code' => $row['state_code'] ?? null,
                'is_group'   => $isGroup,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Seeded ' . count($data) . ' locations.');
    }
}
