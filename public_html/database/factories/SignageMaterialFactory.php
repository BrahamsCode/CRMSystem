<?php

namespace Database\Factories;

use App\Enums\Terminales\SignageFileType;
use App\Models\SignageMaterial;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SignageMaterial> */
class SignageMaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'title' => '素材 '.fake()->numberBetween(1, 99),
            'file_type' => SignageFileType::Image,
            'images' => [['flg' => '1', 'ext' => 'jpg', 'path' => null]],
            'duration_seconds' => 10,
        ];
    }
}
