<?php

namespace Database\Factories;

use App\Models\ClearanceStep;
use App\Models\ClearanceStepEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClearanceStepEvent>
 */
class ClearanceStepEventFactory extends Factory
{
    protected $model = ClearanceStepEvent::class;

    public function definition(): array
    {
        return [
            'clearance_step_id' => ClearanceStep::factory(),
            'actor_user_id' => User::factory()->office(),
            'actor_role' => User::ROLE_OFFICE,
            'action' => 'approved',
            'remarks' => null,
        ];
    }

    public function generated(): static
    {
        return $this->state(fn () => [
            'actor_user_id' => null,
            'actor_role' => 'system',
            'action' => 'generated',
            'remarks' => null,
        ]);
    }

    public function flagged(?string $remarks = null): static
    {
        return $this->state(fn () => [
            'action' => 'flagged',
            'remarks' => $remarks,
        ]);
    }
}
