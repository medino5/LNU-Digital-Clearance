<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\StudentNameFormatter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => $firstName . ' ' . $lastName,
            'first_name' => $firstName,
            'middle_initial' => null,
            'last_name' => $lastName,
            'name_extension' => null,
            'username' => fake()->unique()->userName(),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => \App\Models\User::ROLE_STUDENT,
            'is_student' => true,
            'is_staff' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
            'is_student' => false,
            'is_staff' => false,
        ]);
    }

    public function office(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_STUDENT,
            'is_student' => true,
            'is_staff' => false,
        ]);
    }

    public function namedStudent(
        string $firstName,
        ?string $middleInitial,
        string $lastName,
        ?string $nameExtension = null,
    ): static {
        return $this->student()->state(fn (array $attributes) => [
            'name' => StudentNameFormatter::compose(
                $firstName,
                $middleInitial,
                $lastName,
                $nameExtension
            ),
            'first_name' => $firstName,
            'middle_initial' => $middleInitial,
            'last_name' => $lastName,
            'name_extension' => $nameExtension,
        ]);
    }
}
