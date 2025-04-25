<?php

namespace App\Casts;

use Brick\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class TXAmountCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return Money::ofMinor($value, 'NGN');
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof Money) {
            return $value->getMinorAmount()->toInt();
        }

        return Money::of($value, 'NGN')->getMinorAmount()->toInt();
    }


    public function serialize($model, string $key, $value, array $attributes)
    {
        if ($value instanceof Money) {
            // Return the numeric value for serialization
            return $value->getAmount()->toFloat();
        }
        return $value;
    }
}
