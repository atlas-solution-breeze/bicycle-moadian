<?php

namespace Database\Factories;

use App\Models\MoadianResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MoadianResult>
 */
class MoadianResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => $this->faker->randomElement([1, 2]),
            'reference_number' => $this->faker->uuid,
            'uid' => $this->faker->uuid,
            'response' => $this->faker->text,
        ];
    }
}
