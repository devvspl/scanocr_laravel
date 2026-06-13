<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OdometerTrainingSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('odometer_training_data')->count() > 0) {
            return;
        }

        $samples = [
            // Digital samples
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 45230, 'unit' => 'km', 'ocr' => 'ODO 45230 km TRIP 123.4', 'pattern' => 'standard', 'keywords' => ['odo', 'km', 'trip'], 'difficulty' => 'easy'],
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 89456, 'unit' => 'km', 'ocr' => '89456 KM', 'pattern' => 'standard', 'keywords' => ['km'], 'difficulty' => 'normal'],
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 123456, 'unit' => 'km', 'ocr' => 'ODOMETER 1,23,456 Kms', 'pattern' => 'indian_format', 'keywords' => ['odometer', 'kms'], 'difficulty' => 'normal'],
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 67890, 'unit' => 'km', 'ocr' => '67890', 'pattern' => 'standard', 'keywords' => [], 'difficulty' => 'hard'],
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 9999, 'unit' => 'km', 'ocr' => '9999 km', 'pattern' => 'standard', 'keywords' => ['km'], 'difficulty' => 'easy'],
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 234567, 'unit' => 'km', 'ocr' => '2,34,567 KMS', 'pattern' => 'indian_format', 'keywords' => ['kms'], 'difficulty' => 'normal'],
            ['type' => 'digital', 'source' => 'scanned_document', 'reading' => 15000, 'unit' => 'km', 'ocr' => '15000 KM SERVICE DUE', 'pattern' => 'standard', 'keywords' => ['km', 'service'], 'difficulty' => 'easy'],
            ['type' => 'digital', 'source' => 'scanned_document', 'reading' => 78500, 'unit' => 'km', 'ocr' => 'Odometer Reading: 78500 km', 'pattern' => 'standard', 'keywords' => ['odometer', 'reading', 'km'], 'difficulty' => 'easy'],
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 45000, 'unit' => 'miles', 'ocr' => '45000 MILES', 'pattern' => 'standard', 'keywords' => ['miles'], 'difficulty' => 'normal'],
            ['type' => 'digital', 'source' => 'dashboard_photo', 'reading' => 100000, 'unit' => 'km', 'ocr' => '1,00,000 km', 'pattern' => 'indian_format', 'keywords' => ['km'], 'difficulty' => 'normal'],
            // Analog samples
            ['type' => 'analog', 'source' => 'dashboard_photo', 'reading' => 32450, 'unit' => 'km', 'ocr' => '3 2 4 5 0', 'pattern' => 'spaced', 'keywords' => [], 'difficulty' => 'hard'],
            ['type' => 'analog', 'source' => 'dashboard_photo', 'reading' => 56789, 'unit' => 'km', 'ocr' => '5 6 7 8 9 km', 'pattern' => 'spaced', 'keywords' => ['km'], 'difficulty' => 'hard'],
            ['type' => 'analog', 'source' => 'dashboard_photo', 'reading' => 12340, 'unit' => 'km', 'ocr' => '12340', 'pattern' => 'standard', 'keywords' => [], 'difficulty' => 'normal'],
            ['type' => 'analog', 'source' => 'dashboard_photo', 'reading' => 98765, 'unit' => 'km', 'ocr' => '9 8 7 6 5', 'pattern' => 'spaced', 'keywords' => [], 'difficulty' => 'hard'],
            ['type' => 'analog', 'source' => 'dashboard_photo', 'reading' => 44444, 'unit' => 'km', 'ocr' => '44444 Km', 'pattern' => 'standard', 'keywords' => ['km'], 'difficulty' => 'normal'],
            ['type' => 'analog', 'source' => 'scanned_document', 'reading' => 71230, 'unit' => 'km', 'ocr' => '71230 km Reading', 'pattern' => 'standard', 'keywords' => ['km', 'reading'], 'difficulty' => 'normal'],
            ['type' => 'analog', 'source' => 'scanned_document', 'reading' => 88000, 'unit' => 'km', 'ocr' => 'Odometer 88000', 'pattern' => 'standard', 'keywords' => ['odometer'], 'difficulty' => 'easy'],
            ['type' => 'analog', 'source' => 'dashboard_photo', 'reading' => 25600, 'unit' => 'km', 'ocr' => '25600 KM', 'pattern' => 'standard', 'keywords' => ['km'], 'difficulty' => 'easy'],
        ];

        foreach ($samples as $s) {
            DB::table('odometer_training_data')->insert([
                'odometer_type'          => $s['type'],
                'source_type'            => $s['source'],
                'true_reading'           => $s['reading'],
                'true_unit'              => $s['unit'],
                'ocr_raw_text'           => $s['ocr'],
                'matched_pattern'        => $s['pattern'],
                'keywords_found'         => json_encode($s['keywords']),
                'difficulty_level'       => $s['difficulty'],
                'status'                 => 'active',
                'created_by'             => 1,
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        $this->command->info('Seeded 18 odometer training samples.');
    }
}
