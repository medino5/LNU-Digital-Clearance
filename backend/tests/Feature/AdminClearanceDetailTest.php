<?php

namespace Tests\Feature;

use App\Models\Clearance;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminClearanceDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_fetch_completed_clearance_detail_json(): void
    {
        // This protects the new MAE-36 endpoint: the admin dashboard should be
        // able to fetch a full completed-clearance record with student,
        // semester, step, and event timeline data for history inspection.
        Storage::disk('local')->deleteDirectory('clearances');
        $clearance = $this->createCompletedClearance();
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $response = $this->actingAs($admin)->getJson(
            route('admin.clearances.show', $clearance)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $clearance->id)
            ->assertJsonPath('data.student.student_id_number', '2302314')
            ->assertJsonPath('data.semester.label', '2nd Semester 2024-2025')
            ->assertJsonPath('data.reference_number', $clearance->reference_number)
            ->assertJsonPath('data.counts.total', 5)
            ->assertJsonPath('data.counts.approved', 5)
            ->assertJsonPath('data.steps.0.office_designation.display_name', 'DIGITS Academic Organization Treasurer');

        $this->assertCount(5, $response->json('data.steps'));
        $this->assertNotEmpty($response->json('data.timeline'));
        $this->assertArrayNotHasKey('office_account', $response->json('data.steps.0'));
    }

    public function test_admin_detail_endpoint_rejects_non_admin_users(): void
    {
        // This keeps the history-detail endpoint inside the super admin
        // surface even if a valid office session tries to hit the route.
        $clearance = $this->createCompletedClearance();
        $officeUser = User::where('username', 'bsit.treasurer')->firstOrFail();

        $this->actingAs($officeUser)
            ->getJson(route('admin.clearances.show', $clearance))
            ->assertForbidden();
    }

    public function test_admin_detail_endpoint_only_exposes_completed_history_records(): void
    {
        // This matches the current admin history UX: the detail endpoint is
        // for completed records, not in-progress clearances that still change.
        $studentUser = User::where('username', '2302314')->firstOrFail();
        Sanctum::actingAs($studentUser);
        $this->postJson('/api/clearance')->assertOk();

        $clearance = Clearance::firstOrFail();
        $admin = User::where('username', 'mis.admin')->firstOrFail();

        $this->actingAs($admin)
            ->getJson(route('admin.clearances.show', $clearance))
            ->assertNotFound();
    }

    protected function createCompletedClearance(): Clearance
    {
        $studentUser = User::where('username', '2302314')->firstOrFail();
        Sanctum::actingAs($studentUser);
        $this->postJson('/api/clearance')->assertOk();

        $clearance = Clearance::with('steps.officeDesignation.activeUsers')->firstOrFail();

        foreach ($clearance->steps as $step) {
            $officeUser = $step->officeDesignation->activeUsers->first();
            $this->assertNotNull($officeUser);

            $this->actingAs($officeUser)
                ->post(route('office.steps.process', $step), [
                    'action' => 'approve',
                    'remarks' => 'Approved for admin history detail testing.',
                ])
                ->assertRedirect();
        }

        return $clearance->fresh([
            'semester',
            'student.user',
            'student.program',
            'steps.officeDesignation',
            'steps.events.actor',
        ]);
    }
}
