<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActionSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_pages_include_global_action_search_items(): void
    {
        $response = $this->actingAs($this->seededAdminUser())
            ->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('data-admin-action-search', false)
            ->assertSee('Search admin actions')
            ->assertSee('Add Student')
            ->assertSee('Search Students')
            ->assertSee('Add Program')
            ->assertSee('Add Semester')
            ->assertSee('Routing Configuration')
            ->assertSee('Assign Designation Holder')
            ->assertSee('Add Office Account')
            ->assertSee('Analytics')
            ->assertSee('Download Reports')
            ->assertSee('Download Excel Report')
            ->assertSee('No matching admin action found.');
    }

    public function test_global_action_search_targets_exist_on_admin_pages(): void
    {
        $admin = $this->seededAdminUser();

        $this->actingAs($admin)
            ->get(route('admin.students.index'))
            ->assertOk()
            ->assertSee('id="student-create-card"', false)
            ->assertSee('id="student-records"', false);

        $this->actingAs($admin)
            ->get(route('admin.programs.index'))
            ->assertOk()
            ->assertSee('id="program-create-card"', false)
            ->assertSee('id="program-records"', false);

        $this->actingAs($admin)
            ->get(route('admin.semesters.index'))
            ->assertOk()
            ->assertSee('id="semester-create-card"', false)
            ->assertSee('id="semester-records"', false);

        $this->actingAs($admin)
            ->get(route('admin.office-accounts.index'))
            ->assertOk()
            ->assertSee('id="office-account-create-card"', false)
            ->assertSee('id="office-records"', false);

        $this->actingAs($admin)
            ->get(route('admin.routing.index'))
            ->assertOk()
            ->assertSee('id="routing-configuration"', false);

        $this->actingAs($admin)
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertSee('Analytics')
            ->assertSee('Download Analytics Report');

        $this->actingAs($admin)
            ->get(route('admin.clearance-history.index'))
            ->assertOk()
            ->assertSee('id="download-reports-panel"', false)
            ->assertSee('id="history-export-form"', false);
    }
}
