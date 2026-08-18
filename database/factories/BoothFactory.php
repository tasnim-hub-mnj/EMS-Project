<?php

namespace Database\Factories;

use App\Models\Booth;
use App\Models\Exhibition;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoothFactory extends Factory
{
    protected $model = Booth::class;

    public function definition(): array
    {
        return
        [
            'exhibition_id' => Exhibition::factory(),

            'number'        => $this->faker->unique()->numerify('B-###'),
            'section'       => $this->faker->randomElement(['A', 'B', 'C', 'D', null]),

            'area'          => $this->faker->randomFloat(2, 4, 50),

            'status_inv'    => $this->faker->randomElement(['available', 'booked', 'unavailable']),
            'status'        => $this->faker->randomElement(['available', 'unavailable']),

            'pricing_type'  => $this->faker->randomElement(['total', 'daily']),
            'price'         => $this->faker->randomFloat(2, 50, 500),

            'location'      => $this->faker->sentence(3),

            'services'      => $this->faker->randomElements(
                ['Electricity', 'WiFi', 'Cleaning', 'Security', 'Parking'],
                rand(0, 3)
            ),

            'amenities'     => $this->faker->randomElements(
                ['Chairs', 'Tables', 'Screens', 'Lighting', 'Decoration'],
                rand(0, 3)
            ),

            'description'   => $this->faker->sentence(10),

            'map_x'         => $this->faker->numberBetween(0, 1000),
            'map_y'         => $this->faker->numberBetween(0, 1000),
            'map_width'     => $this->faker->numberBetween(50, 300),
            'map_height'    => $this->faker->numberBetween(50, 300),
        ];
    }
}
