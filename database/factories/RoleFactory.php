<?php

namespace Database\Factories;

use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roles = RoleEnum::toArray();

        return [
            'name' => $this->faker->unique()->randomElement($roles),
            'description' => function (array $attributes) use ($roles) {
                return $this->getUserRole($attributes['name']);
            },
        ];
    }

    private function getUserRole($name)
    {
        switch ($name) {
            case 'user':
                return 'Regular User';
            case 'admin':
                return 'Administrator';
            case 'sub_user':
                return 'Sub User';
            default:
                return 'Unknown Role';
        }
    }
}
