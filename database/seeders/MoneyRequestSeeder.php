<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MoneyRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the money_request_styles table is empty
        if (DB::table('money_request_styles')->count() > 0) {
            $this->command->info('Money request styles table already seeded!');
            return;
        }
        
        $services = [
            [
                'id' => Str::uuid(),
                'name' => 'GenZ',
                'content' => 'How far Idan, I fit see {{currency}} {{amount}} for your hand?',
                'picture' => 'https://res.cloudinary.com/dca2p5xwg/image/upload/v1747389542/af0669b914ccfa5afb0ec522975cf707b2b3cb6f_kwojvr.png',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Millennials',
                'content' => 'I’m in a bit of a tight spot and could really use your help. Could you lend me {{currency}} {{amount}}?
                Appreciate you always!',
                'picture' => 'https://res.cloudinary.com/dca2p5xwg/image/upload/v1747389624/5fa11e53528f01f163a7de12c9e120fa181e31ac_jxyfrx.png',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Custom',
                'content' => null,
                'picture' => null,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('money_request_styles')->insert($services);

        $this->command->info('Money request styles table seeded!');

    }
}
