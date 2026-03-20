<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UatStudentSeeder extends Seeder
{
    public function run(): void
    {
        $programs = Program::query()
            ->whereIn('code', ['BSIT', 'BAEL', 'BSTM', 'BSEntrep'])
            ->get()
            ->keyBy('code');

        $defaultPassword = Hash::make('password');
        $roster = require database_path('seeders/data/uat_students.php');

        foreach ($roster as $studentData) {
            $program = $programs->get($studentData['program_code']);

            if (!$program) {
                throw new RuntimeException('Missing program for UAT roster: ' . $studentData['program_code']);
            }

            $user = User::updateOrCreate(
                ['username' => $studentData['student_id_number']],
                [
                    'name' => $studentData['name'],
                    'email' => null,
                    'password' => $defaultPassword,
                    'role' => User::ROLE_STUDENT,
                    'is_student' => true,
                    'is_staff' => false,
                ]
            );

            Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_id_number' => $studentData['student_id_number'],
                    'program_id' => $program->id,
                    'year_level' => $studentData['year_level'],
                ]
            );
        }
    }
}
