<?php

namespace Database\Seeders;

use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentBirthdayBackfillSeeder extends Seeder
{
    public function run(): void
    {
        Student::query()
            ->whereNull('date_of_birth')
            ->orderBy('student_id_number')
            ->chunkById(500, function ($students): void {
                foreach ($students as $student) {
                    $student->forceFill([
                        'date_of_birth' => $this->dateOfBirthFor($student->student_id_number),
                    ])->save();
                }
            });
    }

    private function dateOfBirthFor(string $studentId): string
    {
        $digits = preg_replace('/\D/', '', $studentId) ?: '0';
        $number = (int) substr($digits, -5);
        $year = 2001 + ($number % 8);
        $month = 1 + ($number % 12);
        $day = 1 + ($number % 28);

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
