<?php

namespace Database\Factories;

use App\Enums\Mypage\AppImageSection;
use App\Models\AppImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppImage> */
class AppImageFactory extends Factory
{
    public function definition(): array
    {
        $section = fake()->randomElement(AppImageSection::cases());
        $position = fake()->numberBetween(0, 5);

        return [
            'section' => $section,
            'position' => $position,
            'path' => AppImage::storagePathFor($section, "{$position}.jpg"),
            'link_url' => fake()->optional()->url(),
            'all_shops_flg' => 1,
        ];
    }
}
