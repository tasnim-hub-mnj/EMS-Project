<?php

namespace Database\Factories;

use App\Models\BoothBooking;
use App\Models\Investor;
use App\Models\Booth;
use App\Models\Copy;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoothBookingFactory extends Factory
{
    protected $model = BoothBooking::class;

    public function definition(): array
    {
        // تواريخ منطقية
        $start = $this->faker->dateTimeBetween('+1 days', '+10 days');
        $end   = $this->faker->dateTimeBetween($start, '+20 days');

        // حساب الأيام
        $days = $start->diff($end)->days + 1;

        return
        [
            'investor_id'        => Investor::factory(),
            'booth_id'           => Booth::factory(),
            'copy_id'            => Copy::factory(),

            'start_date'         => $start->format('Y-m-d'),
            'end_date'           => $end->format('Y-m-d'),
            'days'               => $days,

            'additional_services' => $this->faker->randomElements(
                ['Electricity', 'WiFi', 'Cleaning', 'Security', 'Parking'],
                rand(0, 3)
            ),

            'notes'              => $this->faker->sentence(8),

            'total_price'        => $this->faker->randomFloat(2, 100, 2000),
            'paid_amount'        => $this->faker->randomFloat(2, 0, 1000),

            'services_products'  => $this->faker->randomElements(
                ['Screens', 'Tables', 'Chairs', 'Decoration', 'Lighting'],
                rand(0, 3)
            ),

            'visitors_count'     => $this->faker->numberBetween(0, 5000),

            'status'             => $this->faker->randomElement([
                'pending', 'approved', 'rejected', 'cancelled', 'finished'
            ]),

            'booked_at'          => $this->faker->dateTimeBetween('-10 days', 'now')->format('Y-m-d'),
            'approved_at'        => $this->faker->randomElement([
                null,
                $this->faker->dateTimeBetween('-5 days', 'now')->format('Y-m-d')
            ]),

            'cover_image'        => $this->faker->imageUrl(800, 600, 'booth', true),
        ];
    }
}
