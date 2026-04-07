<?php

namespace Database\Factories;

use App\Models\OfficeDesignation;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfficeDesignation>
 */
class OfficeDesignationFactory extends Factory
{
    protected $model = OfficeDesignation::class;

    public function definition(): array
    {
        return [
            'key' => fake()->unique()->slug(2),
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeDesignation::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
            'is_active' => true,
        ];
    }

    public function academicOrgTreasurer(?Program $program = null): static
    {
        return $this->state(function () use ($program) {
            $resolvedProgram = $program ?? Program::factory()->make();

            return [
                'key' => strtolower($resolvedProgram->code) . '-acad-org-treasurer',
                'display_name' => ($resolvedProgram->org_name ?? $resolvedProgram->code) . ' Academic Organization Treasurer',
                'office_type' => OfficeDesignation::TYPE_ACAD_ORG_TREASURER,
                'program_id' => $program?->id ?? Program::factory(),
                'year_level' => null,
            ];
        });
    }

    public function academicOrgAdviser(?Program $program = null): static
    {
        return $this->state(function () use ($program) {
            $resolvedProgram = $program ?? Program::factory()->make();

            return [
                'key' => strtolower($resolvedProgram->code) . '-acad-org-adviser',
                'display_name' => ($resolvedProgram->org_name ?? $resolvedProgram->code) . ' Academic Organization Adviser',
                'office_type' => OfficeDesignation::TYPE_ACAD_ORG_ADVISER,
                'program_id' => $program?->id ?? Program::factory(),
                'year_level' => null,
            ];
        });
    }

    public function yearLevelTreasurer(int $yearLevel = 3): static
    {
        return $this->state(fn () => [
            'key' => 'year-' . $yearLevel . '-treasurer',
            'display_name' => $this->yearLabel($yearLevel) . ' Level Organization Treasurer',
            'office_type' => OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER,
            'program_id' => null,
            'year_level' => $yearLevel,
        ]);
    }

    public function librarian(): static
    {
        return $this->state(fn () => [
            'key' => 'college-librarian',
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeDesignation::TYPE_LIBRARIAN,
            'program_id' => null,
            'year_level' => null,
        ]);
    }

    public function vpsd(): static
    {
        return $this->state(fn () => [
            'key' => 'vpsd-office',
            'display_name' => 'Vice President for Student Development',
            'office_type' => OfficeDesignation::TYPE_VPSD,
            'program_id' => null,
            'year_level' => null,
        ]);
    }

    protected function yearLabel(int $yearLevel): string
    {
        return match ($yearLevel) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => $yearLevel . 'th Year',
        };
    }
}
