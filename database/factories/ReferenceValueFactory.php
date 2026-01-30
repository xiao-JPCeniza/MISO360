<?php

namespace Database\Factories;

use App\Enums\ReferenceValueGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReferenceValue>
 */
class ReferenceValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_key' => ReferenceValueGroup::Status->value,
            'name' => fake()->unique()->words(2, true),
            'system_seeded' => false,
            'is_active' => true,
        ];
    }
}
