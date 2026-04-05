<?php

namespace Database\Factories;

use App\Models\OfficeAccount;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfficeAccount>
 */
class OfficeAccountFactory extends Factory
{
    protected $model = OfficeAccount::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->office(),
            'display_name' => fake()->name(),
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ];
    }

    public function academicOrgTreasurer(?Program $program = null): static
    {
        return $this->state(fn (array $attributes) => [
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program?->id ?? Program::factory(),
            'year_level' => null,
        ]);
    }

    public function yearLevelTreasurer(int $yearLevel = 3): static
    {
        return $this->state(fn (array $attributes) => [
            'office_type' => OfficeAccount::TYPE_YEAR_LEVEL_TREASURER,
            'program_id' => null,
            'year_level' => $yearLevel,
        ]);
    }

    public function librarian(): static
    {
        return $this->state(fn (array $attributes) => [
            'office_type' => OfficeAccount::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);
    }

    public function vpsd(): static
    {
        return $this->state(fn (array $attributes) => [
            'office_type' => OfficeAccount::TYPE_VPSD,
            'program_id' => null,
            'year_level' => null,
        ]);
    }
}
