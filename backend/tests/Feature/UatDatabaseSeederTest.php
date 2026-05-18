<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UatDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UatDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_only_loads_the_core_system_records(): void
    {
        // This keeps the base application seed lightweight so production-safe
        // bootstrap data does not accidentally include the large UAT roster.
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(7, Program::query()->count());
        $this->assertSame(1, Student::count());
        $this->assertDatabaseHas('students', [
            'student_id_number' => '2302314',
            'section' => '3-1',
        ]);
        $this->assertSame(
            '2005-03-14',
            Student::where('student_id_number', '2302314')->firstOrFail()->date_of_birth->toDateString(),
        );
        $this->assertDatabaseHas('programs', [
            'code' => 'AS',
            'name' => 'Bachelor of Science in Social Work',
            'org_name' => "Junior Social Worker's Association of the Philippines LNU Chapter",
        ]);
        $this->assertDatabaseHas('programs', [
            'code' => 'EC',
            'name' => 'Bachelor of Early Childhood Education',
            'org_name' => 'Early Childhood Educator Association (ECEO)',
        ]);
        $this->assertDatabaseHas('programs', [
            'code' => 'SM',
            'name' => 'Bachelor of Secondary Education Major in Mathematics',
            'org_name' => 'Math Student Society',
        ]);
    }

    public function test_uat_database_seeder_adds_large_demo_roster_and_clearance_records(): void
    {
        // This protects the manual-testing dataset: UAT should always reseed
        // with deterministic students plus historical completed clearances.
        $this->seed(UatDatabaseSeeder::class);

        $this->assertSame(8000, Student::query()->count());
        $this->assertNotNull(Student::query()->where('student_id_number', '2302314')->first());

        $generatedStudents = Student::query()
            ->where('student_id_number', '!=', '2302314')
            ->with(['program', 'user'])
            ->get();

        $this->assertSame(7999, $generatedStudents->count());
        $this->assertSame(
            7999,
            $generatedStudents
                ->pluck('student_id_number')
                ->filter(fn (string $studentId) => str_starts_with($studentId, '2') && strlen($studentId) === 7)
                ->unique()
                ->count()
        );

        foreach ($generatedStudents as $student) {
            $this->assertMatchesRegularExpression('/^2\d{6}$/', $student->student_id_number);
            $this->assertSame($student->student_id_number, $student->user->username);
            $this->assertNotNull($student->date_of_birth);
            $this->assertMatchesRegularExpression('/^[1-4]-[1-6]$/', $student->section);
        }

        foreach ([
            'BSIT' => 1142,
            'BAEL' => 1143,
            'BSTM' => 1143,
            'BSENTREP' => 1143,
            'AS' => 1143,
            'EC' => 1143,
            'SM' => 1142,
        ] as $programCode => $expectedCount) {
            $programId = Program::where('code', $programCode)->value('id');

            $this->assertSame(
                $expectedCount,
                $generatedStudents->where('program_id', $programId)->count(),
                "Expected {$expectedCount} generated students for {$programCode}."
            );
        }

        foreach ([1 => 2002, 2 => 2002, 3 => 2000, 4 => 1995] as $yearLevel => $expectedCount) {
            $this->assertSame(
                $expectedCount,
                $generatedStudents->where('year_level', $yearLevel)->count(),
                "Expected {$expectedCount} generated students for year level {$yearLevel}."
            );
        }

        $this->assertDatabaseHas('users', [
            'username' => '2400001',
            'name' => 'Bianca C. Dela Cruz',
            'first_name' => 'Bianca',
            'middle_initial' => 'C',
            'last_name' => 'Dela Cruz',
            'name_extension' => null,
        ]);

        $this->assertDatabaseHas('students', [
            'student_id_number' => '2407999',
            'year_level' => 4,
            'section' => '4-3',
        ]);
        $this->assertSame(
            '2008-08-20',
            Student::where('student_id_number', '2407999')->firstOrFail()->date_of_birth->toDateString(),
        );

        $this->assertDatabaseHas('students', [
            'student_id_number' => '2404572',
            'year_level' => 1,
            'section' => '1-1',
            'program_id' => Program::where('code', 'AS')->value('id'),
        ]);

        $this->assertDatabaseHas('users', [
            'username' => '2302314',
            'name' => 'John A. Doe',
            'first_name' => 'John',
            'middle_initial' => 'A',
            'last_name' => 'Doe',
            'name_extension' => null,
        ]);

        $this->assertSame(16240, DB::table('clearances')
            ->where('status', 'completed')
            ->where('reference_number', 'like', 'CLR-%')
            ->count());

        $completedBySemester = DB::table('clearances')
            ->select('semester_label', DB::raw('COUNT(*) as total'))
            ->where('status', 'completed')
            ->where('reference_number', 'like', 'CLR-%')
            ->groupBy('semester_label')
            ->pluck('total', 'semester_label');

        foreach ([
            '1st Semester 2023-2024' => 2240,
            '2nd Semester 2023-2024' => 2240,
            '1st Semester 2024-2025' => 3920,
            '2nd Semester 2024-2025' => 3920,
            '1st Semester 2025-2026' => 3920,
        ] as $semesterLabel => $expectedCount) {
            $this->assertSame($expectedCount, (int) $completedBySemester[$semesterLabel]);
        }

        $completedByProgram = DB::table('clearances')
            ->select('program_code', DB::raw('COUNT(*) as total'))
            ->where('status', 'completed')
            ->where('reference_number', 'like', 'CLR-%')
            ->groupBy('program_code')
            ->pluck('total', 'program_code');

        foreach (['BSIT', 'BAEL', 'BSTM', 'BSENTREP', 'AS', 'EC', 'SM'] as $programCode) {
            $this->assertSame(2320, (int) $completedByProgram[$programCode]);
        }

        $this->assertSame(6000, DB::table('clearances')
            ->where('semester_label', '2nd Semester 2025-2026')
            ->whereIn('status', ['in_progress', 'flagged'])
            ->count());

        $this->assertSame(30000, DB::table('clearance_steps')
            ->join('clearances', 'clearance_steps.clearance_id', '=', 'clearances.id')
            ->where('clearances.semester_label', '2nd Semester 2025-2026')
            ->count());
    }
}
