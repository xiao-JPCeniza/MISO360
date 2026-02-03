<?php

namespace Database\Factories;

use App\Enums\ProfileSlideTextPosition;
use App\Models\ProfileSlide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileSlide>
 */
class ProfileSlideFactory extends Factory
{
    protected $model = ProfileSlide::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'image_path' => 'profile-slides/'.fake()->uuid().'.jpg',
            'title' => fake()->words(3, true),
            'subtitle' => fake()->optional()->sentence(),
            'text_position' => fake()->randomElement(array_column(ProfileSlideTextPosition::cases(), 'value')),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
