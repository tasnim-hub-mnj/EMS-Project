<?php

namespace Database\Factories;

use App\Models\BoothImage;
use App\Models\Booth;
use Illuminate\Database\Eloquent\Factories\Factory;

class BoothImageFactory extends Factory
{
    protected $model = BoothImage::class;

    public function definition(): array
    {
        return
        [
            'booth_id' => Booth::factory(),
            'image'    => $this->faker->imageUrl(800, 600, 'booth', true),
        ];
    }
}
