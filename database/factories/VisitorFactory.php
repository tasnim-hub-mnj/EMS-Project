<?php

namespace Database\Factories;

use App\Models\Investor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvestorFactory extends Factory
{
    protected $model = Investor::class;

    public function definition(): array
    {
        return
        [
            'user_id'       => User::factory(), // لكل مستثمر مستخدم جديد
            'company_name'  => $this->faker->company(),
            'trade_name'    => $this->faker->word(), // المجال التجاري
            'location'      => $this->faker->address(), // المقر
            'website'       => $this->faker->url(),
            'activity_type' => $this->faker->randomElement([
                'technology',
                'food&hospitality',
                'fashion',
                'health',
                'education',
                'other'
            ]),
            'bio'           => $this->faker->sentence(12),
            'logo'          => $this->faker->imageUrl(300, 300, 'business', true),
        ];
    }
}
