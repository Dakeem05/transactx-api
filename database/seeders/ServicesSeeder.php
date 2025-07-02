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
        // if (DB::table('services')->count() > 0) {
        //     $this->command->info('Services table already seeded!');
        //     return;
        // }
        
        $services = [
            // Payment Services
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'payments',
            //     'status' => true,
            //     'description' => 'Payment service',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'airtime',
            //     'status' => true,
            //     'description' => 'Airtime service',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'data',
            //     'status' => true,
            //     'description' => 'Data service',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'cabletv',
            //     'status' => true,
            //     'description' => 'Cable TV service',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'electricity',
            //     'status' => true,
            //     'description' => 'Electricity service',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'id' => Str::uuid(),
                'name' => 'banking',
                'status' => true,
                'description' => 'Banking service',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('services')->insert($services);

        $this->command->info('Services table seeded!');

    }
}
