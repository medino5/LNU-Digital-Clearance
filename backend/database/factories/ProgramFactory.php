<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        $suffix = strtoupper(fake()->unique()->lexify('??'));

        return [
            'code' => 'BS' . $suffix,
            'name' => 'Bachelor of Science in ' . fake()->unique()->words(2, true),
            'org_name' => strtoupper(fake()->unique()->lexify('org??')),
        ];
    }
}
