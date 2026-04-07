<?php

namespace Database\Factories;

use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OfficeDesignationAssignment>
 */
class OfficeDesignationAssignmentFactory extends Factory
{
    protected $model = OfficeDesignationAssignment::class;

    public function definition(): array
    {
        return [
            'office_designation_id' => OfficeDesignation::factory(),
            'user_id' => User::factory()->office(),
            'assigned_by_user_id' => null,
            'assigned_at' => now(),
            'released_at' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
            'released_at' => now(),
        ]);
    }
}
