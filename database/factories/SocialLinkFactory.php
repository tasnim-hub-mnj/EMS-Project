<?php

namespace Database\Factories;

use App\Models\SocialLink;
use App\Models\Investor;
use Illuminate\Database\Eloquent\Factories\Factory;

class SocialLinkFactory extends Factory
{
    protected $model = SocialLink::class;

    public function definition(): array
    {
        return
        [
            'investor_id' => Investor::factory(), // ينشئ مستثمر جديد تلقائيًا
            'link'        => $this->faker->url(),
            'type'        => $this->faker->randomElement([
                'linkedin',
                'twitter',
                'instagram',
                'facebook'
            ]),
        ];
    }
}
