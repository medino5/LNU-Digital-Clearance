<?php

namespace Tests\Unit;

use App\Support\AdminClearanceDetailBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $clearance = $this->createCompletedSeededClearance(
            remarks: 'Approved for builder coverage.',
        );

        // Build the admin detail payload directly instead of going through the
        // controller so this test stays focused on the transformer itself.
        $payload = app(AdminClearanceDetailBuilder::class)
            ->build($clearance->fresh());

        // Verify the snapshot and structure that the admin history UI depends
        // on, including the new designation-based step shape.
        $this->assertSame($clearance->id, $payload['id']);
        $this->assertSame('John A. Doe', $payload['student']['name']);
        $this->assertSame('BSIT', $payload['student']['program']['code']);
        $this->assertSame('2nd Semester 2024-2025', $payload['semester']['label']);
        $this->assertCount(5, $payload['steps']);
        $this->assertNotEmpty($payload['timeline']);
        $this->assertArrayNotHasKey('office_account', $payload['steps'][0]);
        $this->assertArrayHasKey('office_designation', $payload['steps'][0]);

        $actions = collect($payload['timeline'])->pluck('action')->all();

        // The timeline should contain both the initial generation event and
        // the later approval events performed by office holders.
        $this->assertContains('generated', $actions);
        $this->assertContains('approved', $actions);
    }
}
