<?php

namespace Database\Factories;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'id_event' => Event::factory(),
            'id_ticket_type' => TicketType::factory(),
            'id_batch' => Batch::factory(),
            'status' => $this->faker->randomElement(['pending', 'paid', 'used', 'refunded']),
            'seat_number' => $this->faker->unique()->numberBetween(1, 1000),
            'is_validated' => $this->faker->boolean(10),
            'ticket_code' => $this->faker->uuid(),
        ];
    }
}
