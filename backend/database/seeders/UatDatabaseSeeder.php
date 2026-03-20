<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UatDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CoreSystemSeeder::class,
            UatStudentSeeder::class,
        ]);
    }
}
