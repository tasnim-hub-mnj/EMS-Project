<?php

namespace Database\Factories;

use App\Models\Organizer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;


/**
 * @extends Factory<Organizer>
 */
class OrganizerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return
        [
            'user_id'       => User::factory(), // لكل منظم مستخدم جديد
            'company_name'  => $this->faker->company(),
            'category'      => $this->faker->randomElement([['Technology'],['Food'],['Fashion'],['Education'],['Health'],['Sports'],]),
            'headquarters'  => $this->faker->city(),
            'reg_number'    => $this->faker->unique()->numerify('########'),
            'location'      => $this->faker->address(),
            'description'   => $this->faker->sentence(10),
            'logo'          => $this->faker->imageUrl(300, 300, 'business', true),
            'file'          =>
            [
                'contract' => Str::random(20) . '.pdf',
                'issued_at' => now()->toDateString(),
            ],
        ];
    }
}
