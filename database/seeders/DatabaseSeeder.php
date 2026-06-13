<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')]
        );

        $this->call([
            CompanySeeder::class,
            DocumentTypeSeeder::class,
            PermissionSeeder::class,
            DepartmentLocationSeeder::class,
            OcrClassificationBasisSeeder::class,
            SeedTitlePatterns::class,
            DepartmentPredictionRulesSeeder::class,
        ]);

        // Assign Super Admin role to the first user
        $firstUser = User::orderBy('id')->first();
        if ($firstUser) {
            $firstUser->assignRole('Super Admin');
        }
    }
}
