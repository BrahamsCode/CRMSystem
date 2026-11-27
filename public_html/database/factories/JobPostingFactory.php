<?php

namespace Database\Factories;

use App\Models\JobCategory;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobPosting> */
class JobPostingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'job_category_id' => JobCategory::factory(),
            'headline' => 'スタッフ募集中！',
            'description' => '接客と商品の陳列をお願いします。',
            'employment_type' => 'アルバイト・パート',
            'requirements' => '未経験者歓迎',
            'work_location' => '大阪市北区',
            'work_hours' => '10:00〜19:00（シフト制）',
            'salary' => '時給1,200円〜',
            'holidays' => '週休2日',
            'benefits' => '交通費支給',
        ];
    }
}
