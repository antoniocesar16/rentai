<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 500, 5000),
            'location' => $this->faker->address(),
            'details' => [
                'bedrooms' => $this->faker->numberBetween(1, 5),
                'bathrooms' => $this->faker->numberBetween(1, 3),
                'area' => $this->faker->numberBetween(50, 300),
            ],
            'photos' => [],
        ];
    }
}
