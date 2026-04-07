<?php

namespace Database\Factories;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeDesignation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClearanceStep>
 */
class ClearanceStepFactory extends Factory
{
    protected $model = ClearanceStep::class;

    public function definition(): array
    {
        return [
            'clearance_id' => Clearance::factory(),
            'office_designation_id' => OfficeDesignation::factory()->librarian(),
            'status' => ClearanceStep::STATUS_AWAITING_ACTION,
            'remarks' => null,
            'signed_at' => null,
            'office_label' => 'College Chief Librarian',
            'office_type' => OfficeDesignation::TYPE_LIBRARIAN,
            'scope_label' => null,
        ];
    }

    public function forDesignation(OfficeDesignation $designation): static
    {
        return $this->state(fn () => [
            'office_designation_id' => $designation->id,
            'office_label' => $designation->display_name,
            'office_type' => $designation->office_type,
            'scope_label' => $designation->scopeLabel(),
        ]);
    }

    public function approved(?string $remarks = null): static
    {
        return $this->state(fn () => [
            'status' => ClearanceStep::STATUS_APPROVED,
            'remarks' => $remarks,
            'signed_at' => now(),
        ]);
    }

    public function flagged(string $remarks): static
    {
        return $this->state(fn () => [
            'status' => ClearanceStep::STATUS_FLAGGED,
            'remarks' => $remarks,
            'signed_at' => null,
        ]);
    }
}
