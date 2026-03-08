<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use App\Models\Designation;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // =========================
        // PROGRAMS
        // =========================
        $english = Program::create([
            'name' => 'BSED English'
        ]);

        $math = Program::create([
            'name' => 'BSED Math'
        ]);

        // =========================
        // ORGANIZATIONS
        // =========================
        $englishOrg = Organization::create([
            'name' => 'English Circle',
            'type' => 'academic',
            'program_id' => $english->id,
            'year_level' => null
        ]);

        $mathOrg = Organization::create([
            'name' => 'Math Student Society',
            'type' => 'academic',
            'program_id' => $math->id,
            'year_level' => null
        ]);

        // Global university-level offices (no specific program)
        $universityOffices = Organization::create([
            'name' => 'University Offices',
            'type' => 'global',
            'program_id' => null,
            'year_level' => null
        ]);

        // =========================
        // DESIGNATIONS
        // =========================
        $englishTreasurer = Designation::create([
            'name' => 'Treasurer',
            'organization_id' => $englishOrg->id
        ]);

        $mathAdviser = Designation::create([
            'name' => 'Adviser',
            'organization_id' => $mathOrg->id
        ]);

        $cashier = Designation::create([
            'name' => 'University Cashier',
            'organization_id' => $universityOffices->id
        ]);

        $library = Designation::create([
            'name' => 'University Library',
            'organization_id' => $universityOffices->id
        ]);

        $cmeDean = Designation::create([
            'name' => 'CME Dean',
            'organization_id' => $universityOffices->id
        ]);

        // =========================
        // STAFF USERS
        // =========================
        $staff1 = User::create([
            'name' => 'Staff English Treasurer',
            'email' => 'english_staff@test.com',
            'password' => Hash::make('password'),
            'is_staff' => true,
            'is_student' => false
        ]);

        $staff2 = User::create([
            'name' => 'Staff Math Adviser',
            'email' => 'math_staff@test.com',
            'password' => Hash::make('password'),
            'is_staff' => true,
            'is_student' => false
        ]);

        // BUGFIX (Sprints 1–2): the demo staff account cashier@lnu.edu.ph was not seeded at all,
        // so login always returned "invalid credentials" even with the correct password.
        $cashierStaff = User::create([
            'name' => 'University Cashier',
            'email' => 'cashier@lnu.edu.ph',
            'password' => Hash::make('password'),
            'is_staff' => true,
            'is_student' => false,
        ]);

        // Attach designations to staff
        $staff1->designations()->attach($englishTreasurer->id);
        $staff2->designations()->attach($mathAdviser->id);
        $cashierStaff->designations()->attach($cashier->id);

        // =========================
        // STUDENT USER
        // =========================
        $student = User::create([
            'name' => 'Test Student',
            'email' => 'student@test.com',
            'password' => Hash::make('password'),
            'is_student' => true,
            'is_staff' => false,
            'program_id' => $english->id,
            'year_level' => 3
        ]);

        // Attach student to English organization
        $englishOrg->students()->attach($student->id);
    }
}