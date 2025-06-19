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
        // if (DB::table('service_providers')->count() > 0) {
        //     $this->command->info('Services table already seeded!');
        //     return;
        // }

        // Get existing service IDs
        $paymentServiceId = DB::table('services')->where('name', 'payments')->value('id');
        $airtimeServiceId = DB::table('services')->where('name', 'airtime')->value('id');
        $dataServiceId = DB::table('services')->where('name', 'data')->value('id');
        $cabletvServiceId = DB::table('services')->where('name', 'cabletv')->value('id');
        $electricityServiceId = DB::table('services')->where('name', 'electricity')->value('id');

        $serviceProviders = [
            // Payment Service Providers
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'paystack',
            //     'service_id' => $paymentServiceId,
            //     'status' => false,
            //     'description' => 'Payment processing for Nigeria and Ghana',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'flutterwave',
            //     'service_id' => $paymentServiceId,
            //     'status' => false,
            //     'description' => 'Payment processing for Africa',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            // [
            //     'id' => Str::uuid(),
            //     'name' => 'safehaven',
            //     'service_id' => $paymentServiceId,
            //     'status' => true,
            //     'description' => 'Payment processing for Nigeria',
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ],
            [
                'id' => Str::uuid(),
                'name' => 'safehaven',
                'service_id' => $airtimeServiceId,
                'status' => true,
                'description' => 'Safehaven airtime provider',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'safehaven',
                'service_id' => $dataServiceId,
                'status' => true,
                'description' => 'Safehaven data provider',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'safehaven',
                'service_id' => $cabletvServiceId,
                'status' => true,
                'description' => 'Safehaven cabletv provider',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'safehaven',
                'service_id' => $electricityServiceId,
                'status' => true,
                'description' => 'Safehaven electricity provider',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('service_providers')->insert($serviceProviders);

        $this->command->info('Service Providers table seeded!');
    }
}