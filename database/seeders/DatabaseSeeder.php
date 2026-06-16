<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModelSeeder::class,  // AI models first (no dependencies)
            AdminSeeder::class,  // Admin user (depends on users table)
        ]);
    }
}
