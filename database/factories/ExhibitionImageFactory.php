<?php

namespace Database\Factories;

use App\Models\ExhibitionImage;
use App\Models\Exhibition;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExhibitionImageFactory extends Factory
{
    protected $model = ExhibitionImage::class;

    public function definition(): array
    {
        return
        [
            'exhibition_id' => Exhibition::factory(),
            'image'         => $this->faker->imageUrl(800, 600, 'event', true),
        ];
    }
}
