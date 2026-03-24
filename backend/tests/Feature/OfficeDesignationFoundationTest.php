<?php

namespace Tests\Feature;

use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
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
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'org_name' => 'DIGITS',
        ]);

        $officeUser = User::create([
            'name' => 'Office User',
            'username' => 'office.user',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $designationA = OfficeDesignation::create([
            'key' => 'bsit-acad-org-treasurer',
            'display_name' => 'DIGITS Academic Organization Treasurer',
            'office_type' => OfficeDesignation::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
        ]);

        $designationB = OfficeDesignation::create([
            'key' => 'college-librarian',
            'display_name' => 'College Chief Librarian',
            'office_type' => OfficeDesignation::TYPE_LIBRARIAN,
        ]);

        OfficeDesignationAssignment::create([
            'office_designation_id' => $designationA->id,
            'user_id' => $officeUser->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        OfficeDesignationAssignment::create([
            'office_designation_id' => $designationB->id,
            'user_id' => $officeUser->id,
            'assigned_at' => now(),
            'is_active' => true,
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
        $designation = OfficeDesignation::create([
            'key' => 'vpsd-office',
            'display_name' => 'Vice President for Student Development',
            'office_type' => OfficeDesignation::TYPE_VPSD,
        ]);

        $this->assertCount(0, $designation->activeAssignments);
        $this->assertCount(0, $designation->activeUsers);
    }
}
