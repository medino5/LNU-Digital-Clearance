<?php

namespace Tests\Feature;

use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\Program;
use App\Models\User;
use App\Support\OfficeDesignationBackfill;
use Database\Seeders\CoreSystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeDesignationBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_system_seeder_backfills_office_accounts_into_designations(): void
    {
        $this->seed(CoreSystemSeeder::class);

        $this->assertSame(14, OfficeAccount::query()->count());
        $this->assertSame(14, OfficeDesignation::query()->count());
        $this->assertSame(14, OfficeDesignationAssignment::query()->where('is_active', true)->count());

        $bsitTreasurer = User::query()
            ->where('username', 'bsit.treasurer')
            ->firstOrFail();

        $this->assertSame(
            ['bsit-acad-org-treasurer'],
            $bsitTreasurer->fresh()->activeOfficeDesignations->pluck('key')->values()->all()
        );

        $designation = OfficeDesignation::query()
            ->where('key', 'bsit-acad-org-treasurer')
            ->firstOrFail();

        $this->assertSame('DIGITS Academic Organization Treasurer', $designation->display_name);
        $this->assertSame(OfficeDesignation::TYPE_ACAD_ORG_TREASURER, $designation->office_type);
        $this->assertSame('BSIT', $designation->program?->code);
    }

    public function test_backfill_is_idempotent_for_shared_designations(): void
    {
        $program = Program::create([
            'code' => 'BSIT',
            'name' => 'Bachelor of Science in Information Technology',
            'org_name' => 'DIGITS',
        ]);

        $firstOfficeUser = User::create([
            'name' => 'First DIGITS Treasurer',
            'username' => 'digits.one',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        $secondOfficeUser = User::create([
            'name' => 'Second DIGITS Treasurer',
            'username' => 'digits.two',
            'password' => bcrypt('password'),
            'role' => User::ROLE_OFFICE,
            'is_student' => false,
            'is_staff' => true,
        ]);

        OfficeAccount::create([
            'user_id' => $firstOfficeUser->id,
            'display_name' => 'DIGITS Academic Organization Treasurer',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
        ]);

        OfficeAccount::create([
            'user_id' => $secondOfficeUser->id,
            'display_name' => 'DIGITS Academic Organization Treasurer',
            'office_type' => OfficeAccount::TYPE_ACAD_ORG_TREASURER,
            'program_id' => $program->id,
        ]);

        $backfill = new OfficeDesignationBackfill();
        $backfill->run();
        $backfill->run();

        $designation = OfficeDesignation::query()->where('key', 'bsit-acad-org-treasurer')->firstOrFail();

        $this->assertSame(1, OfficeDesignation::query()->count());
        $this->assertSame(
            2,
            OfficeDesignationAssignment::query()
                ->where('office_designation_id', $designation->id)
                ->where('is_active', true)
                ->count()
        );
        $this->assertEqualsCanonicalizing(
            ['digits.one', 'digits.two'],
            $designation->fresh()
                ->activeUsers
                ->pluck('username')
                ->values()
                ->all()
        );
    }
}
