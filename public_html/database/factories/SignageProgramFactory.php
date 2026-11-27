<?php

namespace Database\Factories;

use App\Enums\Terminales\SignageContentMode;
use App\Models\SignageProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SignageProgram> */
class SignageProgramFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'name' => '放映プログラム '.fake()->numberBetween(1, 99),
            'content_mode' => SignageContentMode::Files,
            'terminal_types' => ['1'],
        ];
    }
}
