<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            HospitalDataSeeder::class,
        ]);

        if (! app()->isProduction()) {
            $this->call([
                StaffUserSeeder::class,
            ]);
        }
    }
}
