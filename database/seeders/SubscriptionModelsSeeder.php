<?php

namespace Database\Seeders;

use App\Enums\Subscription\ModelNameEnum;
use App\Enums\Subscription\ModelStatusEnum;
use App\Models\Business\SubscriptionModel;
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
                'serial' => 1,
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
                'has_discount' => false,
                'discount' => 0,
                'discount_amount' => 0,
                'status' => ModelStatusEnum::ACTIVE
            ],
            [
                'id' => Str::uuid(),
                'name' => ModelNameEnum::STARTUP,
                'serial' => 2,
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
                'full_amount' => Money::of(3000, 'NGN')->getAmount(),
                'has_discount' => true,
                'discount' => 10,
                'discount_amount' => Money::of(3000, 'NGN')
                    ->multipliedBy(0.10)  // 6%
                    ->getAmount(),
                'amount' => Money::of(3000, 'NGN')
                    ->minus(Money::of(3000, 'NGN')->multipliedBy(0.10))
                    ->getAmount(),
                'status' => ModelStatusEnum::ACTIVE
            ],
            [
                'id' => Str::uuid(),
                'serial' => 3,
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
                'full_amount' => Money::of(10000, 'NGN')->getAmount(),
                'has_discount' => true,
                'discount' => 10,
                'discount_amount' => Money::of(10000, 'NGN')
                    ->multipliedBy(0.10)  // 6%
                    ->getAmount(),
                'amount' => Money::of(10000, 'NGN')
                    ->minus(Money::of(10000, 'NGN')->multipliedBy(0.10))
                    ->getAmount(),
                'status' => ModelStatusEnum::ACTIVE
            ],
            [
                'id' => Str::uuid(),
                'name' => ModelNameEnum::ENTERPRISE,
                'serial' => 4,
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

        foreach ($subscription_models as $model) {
            SubscriptionModel::create($model);
        }
        // DB::table('subscription_models')->insert($subscription_models);

        $this->command->info('Subscription Models table seeded!');

    }
}
