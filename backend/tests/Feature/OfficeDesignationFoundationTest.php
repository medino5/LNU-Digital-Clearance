<?php

namespace Tests\Feature;

use App\Models\OfficeDesignation;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeDesignationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_office_user_can_hold_multiple_active_designations(): void
    {
        // This protects the new teacher-requested model: one office user can
        // hold more than one active designation at the same time.
        $program = Program::factory()->create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'org_name' => 'DIGITS',
        ]);

        $officeUser = User::factory()->office()->create([
            'name' => 'Office User',
            'username' => 'office.user',
        ]);

        $designationA = OfficeDesignation::factory()
            ->academicOrgTreasurer($program)
            ->create();
        $designationB = OfficeDesignation::factory()
            ->librarian()
            ->create();

        \App\Models\OfficeDesignationAssignment::factory()->create([
            'office_designation_id' => $designationA->id,
            'user_id' => $officeUser->id,
        ]);

        \App\Models\OfficeDesignationAssignment::factory()->create([
            'office_designation_id' => $designationB->id,
            'user_id' => $officeUser->id,
        ]);

        $this->assertCount(2, $officeUser->fresh()->activeOfficeDesignations);
        $this->assertEqualsCanonicalizing(
            ['DIGITS Academic Organization Treasurer', 'College Chief Librarian'],
            $officeUser->fresh()
                ->activeOfficeDesignations
                ->pluck('display_name')
                ->values()
                ->all()
        );
    }

    public function test_unassigned_designation_has_no_active_holders(): void
    {
        // This locks in the MAE-37 foundation rule: a designation can exist
        // before it has a current holder, and no office user owns it yet.
        $designation = OfficeDesignation::factory()->vpsd()->create();

        $this->assertCount(0, $designation->activeAssignments);
        $this->assertCount(0, $designation->activeUsers);
    }
}
