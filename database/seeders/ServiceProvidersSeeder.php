<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceProvidersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if the services table is empty
        if (DB::table('service_providers')->count() > 0) {
            $this->command->info('Services table already seeded!');
            return;
        }

        // Get existing service IDs
        $paymentServiceId = DB::table('services')->where('name', 'payments')->value('id');

        $serviceProviders = [
            // Payment Service Providers
            [
                'id' => Str::uuid(),
                'name' => 'paystack',
                'service_id' => $paymentServiceId,
                'status' => false,
                'description' => 'Payment processing for Nigeria and Ghana',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'flutterwave',
                'service_id' => $paymentServiceId,
                'status' => true,
                'description' => 'Payment processing for Africa',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('service_providers')->insert($serviceProviders);

        $this->command->info('Service Providers table seeded!');
    }
}