<?php

namespace Database\Factories;

use App\Models\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Semester>
 */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        $startYear = fake()->numberBetween(2024, 2027);

        return [
            'label' => fake()->randomElement(['1st', '2nd']) . ' Semester ' . $startYear . '-' . ($startYear + 1),
            'academic_year' => $startYear . '-' . ($startYear + 1),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
