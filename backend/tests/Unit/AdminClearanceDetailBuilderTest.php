<?php

namespace Tests\Unit;

use App\Models\Clearance;
use App\Models\User;
use App\Support\AdminClearanceDetailBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminClearanceDetailBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_builder_returns_student_snapshot_steps_and_flat_timeline_for_admin_history(): void
    {
        // This keeps the payload builder honest: the admin detail endpoint
        // should expose a stable history shape with student snapshot data,
        // step metadata, and a flattened event timeline for future UI work.
        Storage::disk('local')->deleteDirectory('clearances');
        $studentUser = User::where('username', '2302314')->firstOrFail();
        Sanctum::actingAs($studentUser);
        $this->postJson('/api/clearance')->assertOk();

        $clearance = Clearance::with('steps.officeAccount.user')->firstOrFail();

        foreach ($clearance->steps as $step) {
            $this->actingAs($step->officeAccount->user)
                ->post(route('office.steps.process', $step), [
                    'action' => 'approve',
                    'remarks' => 'Approved for builder coverage.',
                ])
                ->assertRedirect();
        }

        $payload = app(AdminClearanceDetailBuilder::class)
            ->build($clearance->fresh());

        $this->assertSame($clearance->id, $payload['id']);
        $this->assertSame('John A. Doe', $payload['student']['name']);
        $this->assertSame('BSIT', $payload['student']['program']['code']);
        $this->assertSame('2nd Semester 2024-2025', $payload['semester']['label']);
        $this->assertCount(5, $payload['steps']);
        $this->assertNotEmpty($payload['timeline']);

        $actions = collect($payload['timeline'])->pluck('action')->all();

        $this->assertContains('generated', $actions);
        $this->assertContains('approved', $actions);
    }
}
