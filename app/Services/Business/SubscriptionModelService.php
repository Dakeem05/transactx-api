<?php

namespace App\Services\Business;

use App\Models\Business\SubscriptionModel;
use App\Models\Settings;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionModelService
{

    /**
     * Get Subscription model by id
     * @param string $id
     * @return SubscriptionModel|null
     */
    public function getById(string $id)
    {
        return SubscriptionModel::findOrFail($id)->first();
    }


    /**
     * Returns all Subscription models
     * @return SubscriptionModel[]|Collection
     */
    public function getModels()
    {
        return SubscriptionModel::latest()->get();
    }


    /**
     * Create a new Subscription Model
     * @param string $name
     * @param array $features
     * @param bool $hasDiscount
     * @param int $discount
     * @param float $amount
     * @return SubscriptionModel|\Illuminate\Database\Eloquent\Model
     */
    public function createModel(string $name, array $features, bool $hasDiscount, int $discount, float $amount)
    {
        $full_amount = Money::of($amount, Settings::where('name', 'currency')->first()->value);
        $discount_amount = 0;
        $new_amount = $full_amount;

        if ($hasDiscount) {
            $discount_amount = $full_amount->allocate($discount)[0];
            $new_amount = $full_amount->minus($discount_amount);
        }

        return SubscriptionModel::create([
            'name' => $name,
            'features' => $features,
            'has_discount' => $hasDiscount,
            'discount' => $discount,
            'amount' => $new_amount,
            'discount_amount' => $discount_amount,
            'full_amount' => $full_amount,
            'status' => 'ACTIVE',
        ]);
    }


    /**
     * Delete an existing Subscription Model
     * @param \App\Models\Business\SubscriptionModel $subscriptionModel
     * @return bool|null
     */
    public function deleteModel(SubscriptionModel $subscriptionModel)
    {
        return $subscriptionModel->delete();
    }
}
