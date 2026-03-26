<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UatStudentSeeder extends Seeder
{
    public function run(): void
    {
        $programs = Program::query()->get()->keyBy('code');
        $students = require database_path('seeders/data/uat_students.php');

        foreach ($students as $studentData) {
            $program = $programs->get($studentData['program_code']);

            if (!$program) {
                continue;
            }

            $user = User::updateOrCreate(
                ['username' => $studentData['student_id_number']],
                [
                    'name' => $studentData['name'],
                    'email' => null,
                    'password' => Hash::make('password'),
                    'role' => User::ROLE_STUDENT,
                    'is_student' => true,
                    'is_staff' => false,
                ]
            );

            Student::updateOrCreate(
                ['student_id_number' => $studentData['student_id_number']],
                [
                    'user_id' => $user->id,
                    'program_id' => $program->id,
                    'year_level' => $studentData['year_level'],
                ]
            );
        }
    }
}
