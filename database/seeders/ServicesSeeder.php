<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the services table is empty
        if (DB::table('services')->count() > 0) {
            $this->command->info('Services table already seeded!');
            return;
        }
        
        $services = [
            // Payment Services
            [
                'id' => Str::uuid(),
                'name' => 'payments',
                'status' => true,
                'description' => 'Payment service',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('services')->insert($services);

        $this->command->info('Services table seeded!');

    }
}
