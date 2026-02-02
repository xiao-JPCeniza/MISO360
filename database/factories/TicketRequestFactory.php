<?php

namespace Database\Factories;

use App\Models\NatureOfRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TicketRequest>
 */
class TicketRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $natureOfRequest = NatureOfRequest::query()->first() ?? NatureOfRequest::create([
            'name' => 'System Development',
            'is_active' => true,
        ]);

        return [
            'control_ticket_number' => sprintf(
                'CTN-%s-%s',
                now()->format('Ymd'),
                $this->faker->unique()->numerify('####')
            ),
            'nature_of_request_id' => $natureOfRequest->id,
            'user_id' => User::factory(),
            'description' => $this->faker->paragraphs(2, true),
            'has_qr_code' => false,
            'qr_code_number' => null,
            'attachments' => null,
        ];
    }
}
