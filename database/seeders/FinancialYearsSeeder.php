<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialYearsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if financial years already exist
        $existingCount = DB::table('financial_years')->count();
        
        if ($existingCount > 0) {
            $this->command->info('Financial years already exist. Skipping seeding.');
            return;
        }

        $financialYears = [];
        $currentYear = Carbon::now()->year;
        $currentMonth = Carbon::now()->month;
        
        // Determine the current financial year
        // Indian FY starts from April 1st
        if ($currentMonth >= 4) {
            // After April, current FY is current year to next year
            $currentFYStart = $currentYear;
        } else {
            // Before April, current FY is previous year to current year
            $currentFYStart = $currentYear - 1;
        }

        // Generate 6 financial years oldest-first so the latest year gets the highest ID
        // IDs: 1 = oldest (5 years ago), 6 = current year
        for ($i = 5; $i >= 0; $i--) {
            $fyStartYear = $currentFYStart - $i;
            $fyEndYear = $fyStartYear + 1;

            $startDate = Carbon::create($fyStartYear, 4, 1);
            $endDate = Carbon::create($fyEndYear, 3, 31);

            $financialYears[] = [
                'label'      => sprintf('FY %d-%02d', $fyStartYear, $fyEndYear % 100),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date'   => $endDate->format('Y-m-d'),
                'is_current' => ($i === 0) ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('financial_years')->insert($financialYears);
        
        $this->command->info('Successfully seeded ' . count($financialYears) . ' financial years.');
    }
}
