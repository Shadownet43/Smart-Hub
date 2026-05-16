<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Equipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::parse(fake()->dateTimeBetween('-2 weeks', '+4 weeks'));
        $durationHours = fake()->numberBetween(1, 8);
        $end = (clone $start)->addHours($durationHours);

        return [
            'user_id' => User::factory(),
            'equipment_id' => Equipment::factory(),
            'start_time' => $start,
            'end_time' => $end,
            'status' => fake()->randomElement(array_map(
                static fn (BookingStatus $s): string => $s->value,
                BookingStatus::cases()
            )),
            'notes' => fake()->optional(0.6)->sentence(),
            'checked_in_at' => null,
            'checked_out_at' => null,
            'approved_by' => null,
        ];
    }
}
