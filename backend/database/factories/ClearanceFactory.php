<?php

namespace Database\Factories;

use App\Models\Clearance;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clearance>
 */
class ClearanceFactory extends Factory
{
    protected $model = Clearance::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'semester_id' => Semester::factory(),
            'status' => Clearance::STATUS_IN_PROGRESS,
            'reference_number' => null,
            'completed_at' => null,
            'pdf_path' => null,
            'student_name' => 'Sample Student',
            'student_id_number' => '2300001',
            'year_level' => 1,
            'program_code' => 'BSIT',
            'program_name' => 'Bachelor of Science in Information Technology',
            'organization_name' => 'DIGITS',
            'semester_label' => '1st Semester 2024-2025',
        ];
    }

    public function forStudentAndSemester(Student $student, Semester $semester): static
    {
        return $this->state(function () use ($student, $semester) {
            $student->loadMissing('user', 'program');

            return [
                'student_id' => $student->id,
                'semester_id' => $semester->id,
                'student_name' => $student->displayName(),
                'student_id_number' => $student->student_id_number,
                'year_level' => $student->year_level,
                'program_code' => $student->program?->code,
                'program_name' => $student->program?->name,
                'organization_name' => $student->program?->org_name,
                'semester_label' => $semester->label,
            ];
        });
    }
}
