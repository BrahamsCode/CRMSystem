<?php

namespace Database\Factories;

use App\Models\JobCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobCategory> */
class JobCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'name' => fake()->randomElement(['ホールスタッフ', 'キッチンスタッフ', '販売スタッフ', '店長候補']),
        ];
    }
}
