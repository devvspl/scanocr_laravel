<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (DocumentType::systemTypes() as $type) {
            DocumentType::firstOrCreate(
                ['key' => $type['key']],
                array_merge($type, ['is_active' => true, 'is_system' => true, 'created_by' => 1])
            );
        }
    }
}
