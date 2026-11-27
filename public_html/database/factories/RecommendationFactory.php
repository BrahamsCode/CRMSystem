<?php

namespace Database\Factories;

use App\Models\Recommendation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Recommendation> */
class RecommendationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'published_on' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
            'title' => 'おすすめ '.fake()->numberBetween(1, 99),
            'body' => '今月のおすすめメニューです。',
            'link_url' => fake()->optional()->url(),
            'push_flg' => 0,
        ];
    }
}
