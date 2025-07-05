<?php

namespace Database\Seeders;

use App\Enums\Subscription\ModelNameEnum;
use App\Enums\Subscription\ModelStatusEnum;
use Brick\Money\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionModelsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if the subscription models table is empty
        if (DB::table('subscription_models')->count() > 0) {
            $this->command->info('Subscription Models table already seeded!');
            return;
        }
        
        $subscription_models = [
            [
                'id' => Str::uuid(),
                'name' => ModelNameEnum::FREE,
                'features' => json_encode([
                    'auto_bank_transaction_sync' => [
                        'daily_limit' => 1,
                        'monthly_limit' => 30,
                        'duration' => 10,
                    ],
                    'manual_bank_transaction_sync' => [
                        'amount' => 20
                    ],
                    'linked_bank_accounts' => [
                        'limit' => 1,
                    ],
                    'sub_accounts' => [
                        'limit' => 0,
                    ],
                    'customization' => [
                        'available' => false,
                    ],
                ]),
                'amount' => Money::of(0, 'NGN')->getAmount(),
                'full_amount' => Money::of(0, 'NGN')->getAmount(),
                'status' => ModelStatusEnum::ACTIVE
            ],
            [
                'id' => Str::uuid(),
                'name' => ModelNameEnum::STARTUP,
                'features' => json_encode([
                    'auto_bank_transaction_sync' => [
                        'daily_limit' => 5,
                        'monthly_limit' => 150,
                        'duration' => 10,
                    ],
                    'manual_bank_transaction_sync' => [
                        'amount' => 20
                    ],
                    'linked_bank_accounts' => [
                        'limit' => 2,
                    ],
                    'sub_accounts' => [
                        'limit' => 1,
                    ],
                    'customization' => [
                        'available' => false,
                    ],
                ]),
                'amount' => Money::of(3000, 'NGN')->getAmount(),
                'full_amount' => Money::of(3000, 'NGN')->getAmount(),
                'status' => ModelStatusEnum::ACTIVE
            ],
            [
                'id' => Str::uuid(),
                'name' => ModelNameEnum::GROWTH,
                'features' => json_encode([
                    'auto_bank_transaction_sync' => [
                        'daily_limit' => 24,
                        'monthly_limit' => 720,
                        'duration' => 10,
                    ],
                    'manual_bank_transaction_sync' => [
                        'amount' => 20
                    ],
                    'linked_bank_accounts' => [
                        'limit' => 5,
                    ],
                    'sub_accounts' => [
                        'limit' => 3,
                    ],
                    'customization' => [
                        'available' => false,
                    ],
                ]),
                'amount' => Money::of(10000, 'NGN')->getAmount(),
                'full_amount' => Money::of(10000, 'NGN')->getAmount(),
                'status' => ModelStatusEnum::ACTIVE
            ],
            [
                'id' => Str::uuid(),
                'name' => ModelNameEnum::ENTERPRISE,
                'features' => json_encode([
                    'auto_bank_transaction_sync' => [
                        'daily_limit' => 50,
                        'monthly_limit' => 1500,
                        'duration' => 10,
                    ],
                    'manual_bank_transaction_sync' => [
                        'amount' => 15
                    ],
                    'linked_bank_accounts' => [
                        'limit' => 20,
                    ],
                    'sub_accounts' => [
                        'limit' => 'unlimited',
                    ],
                    'customization' => [
                        'available' => true,
                    ],
                ]),
                'amount' => Money::of(0, 'NGN')->getAmount(),
                'full_amount' => Money::of(0, 'NGN')->getAmount(),
                'status' => ModelStatusEnum::ACTIVE
            ]
        ];

        DB::table('subscription_models')->insert($subscription_models);

        $this->command->info('Subscription Models table seeded!');

    }
}
