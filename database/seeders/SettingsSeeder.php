<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    //     if (DB::table('settings')->count() > 0) {
    //         $this->command->info('Settings table already seeded!');
    //         return;
    //     }
        
        $settings = [
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'currency',
            //     'value' => 'NGN',
            //     'description' => 'The active currency for the application',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'country',
            //     'value' => 'NG',
            //     'description' => 'The active currency for the application',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'id' => Str::uuid(),
                'name' => 'min_transaction',
                'value' => '100',
                'description' => 'The minimum transfer',
                'created_at' => now(),
                'updated_at' => now(),
            ]
            
        ];

        DB::table('settings')->insert($settings);

        $this->command->info('Settings table seeded!');

    }
}
