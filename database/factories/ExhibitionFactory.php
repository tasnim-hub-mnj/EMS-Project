<?php

namespace Database\Factories;

use App\Models\Exhibition;
use App\Models\Organizer;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExhibitionFactory extends Factory
{
    protected $model = Exhibition::class;

    public function definition(): array
    {
        return
        [
            'organizer_id'        => Organizer::factory(),

            'name'                => $this->faker->sentence(3),
            'type'                => $this->faker->randomElement([
                'Technology', 'Food', 'Fashion', 'Education', 'Health', 'Sports'
            ]),

            'start_date'          => $this->faker->dateTimeBetween('+10 days', '+20 days')->format('Y-m-d'),
            'end_date'            => $this->faker->dateTimeBetween('+21 days', '+30 days')->format('Y-m-d'),

            'location'            => $this->faker->address(),
            'description'         => $this->faker->sentence(12),
            'city'                => $this->faker->city(),

            'status'              => $this->faker->randomElement([
                'far', 'upcoming', 'ongoing', 'finished', 'hidden'
            ]),

            'available_booths'    => $this->faker->numberBetween(0, 50),
            'total_booths'        => $this->faker->numberBetween(10, 100),
            'total_sponser_events'=> $this->faker->numberBetween(0, 20),
            'visitors_count'      => $this->faker->numberBetween(0, 5000),

            'sectors'             => $this->faker->randomElements(
                ['Technology', 'Food', 'Fashion', 'Education', 'Health', 'Sports'],
                rand(1, 3)
            ),

            'extra_services'      => $this->faker->randomElements(
                ['Cleaning', 'Security', 'Electricity', 'WiFi', 'Parking'],
                rand(0, 3)
            ),

            'is_paid'             => $this->faker->boolean(),
            'ticket_price'        => $this->faker->randomFloat(2, 0, 50),

            'map_built'           => false,

            'working_hours'       => $this->faker->randomFloat(1, 4, 12),

            'image'               => $this->faker->imageUrl(600, 400, 'event', true),
        ];
    }
}
