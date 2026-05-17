<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\StudentRegistrationRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<StudentRegistrationRequest>
 */
class StudentRegistrationRequestFactory extends Factory
{
    protected $model = StudentRegistrationRequest::class;

    public function definition(): array
    {
        return [
            'student_id_number' => (string) $this->faker->unique()->numberBetween(2300000, 2499999),
            'first_name' => $this->faker->firstName(),
            'middle_initial' => strtoupper($this->faker->randomLetter()),
            'last_name' => $this->faker->lastName(),
            'name_extension' => null,
            'email' => $this->faker->unique()->userName() . '@lnu.edu.ph',
            'program_id' => Program::factory(),
            'year_level' => $this->faker->numberBetween(1, 4),
            'section' => null,
            'date_of_birth' => $this->faker->dateTimeBetween('-25 years', '-16 years')->format('Y-m-d'),
            'password' => Hash::make('password'),
            'status' => StudentRegistrationRequest::STATUS_PENDING,
        ];
    }
}
