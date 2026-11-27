<?php

namespace Database\Factories;

use App\Enums\Terminales\SignageContentMode;
use App\Models\SignageDisplay;
use App\Models\SignageProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SignageDisplay> */
class SignageDisplayFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => null,
            'signage_program_id' => SignageProgram::factory(),
            'name' => 'ディスプレイ '.fake()->numberBetween(1, 99),
            'content_mode' => SignageContentMode::Files,
            'terminal_types' => ['1'],
        ];
    }
}
