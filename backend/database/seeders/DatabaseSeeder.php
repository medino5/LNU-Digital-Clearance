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

        // Attach designations to staff
        $staff1->designations()->attach($englishTreasurer->id);
        $staff2->designations()->attach($mathAdviser->id);

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