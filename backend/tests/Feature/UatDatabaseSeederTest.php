<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\UatDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_database_seeder_only_loads_the_core_system_records(): void
    {
        // This keeps the base application seed lightweight so production-safe
        // bootstrap data does not accidentally include the large UAT roster.
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(1, Student::count());
        $this->assertDatabaseHas('students', [
            'student_id_number' => '2302314',
        ]);
    }

    public function test_uat_database_seeder_adds_a_balanced_100_student_roster(): void
    {
        // This protects the manual-testing dataset: UAT should always reseed
        // with 100 deterministic student accounts plus the canonical demo student.
        $this->seed(UatDatabaseSeeder::class);

        $this->assertSame(101, Student::query()->count());
        $this->assertNotNull(Student::query()->where('student_id_number', '2302314')->first());

        $generatedStudents = Student::query()
            ->where('student_id_number', '!=', '2302314')
            ->with(['program', 'user'])
            ->get();

        $this->assertSame(100, $generatedStudents->count());
        $this->assertSame(
            100,
            $generatedStudents
                ->pluck('student_id_number')
                ->filter(fn (string $studentId) => str_starts_with($studentId, '2') && strlen($studentId) === 7)
                ->unique()
                ->count()
        );

        foreach ($generatedStudents as $student) {
            $this->assertMatchesRegularExpression('/^2\d{6}$/', $student->student_id_number);
            $this->assertSame($student->student_id_number, $student->user->username);
        }

        foreach (['BSIT', 'BAEL', 'BSTM', 'BSEntrep'] as $programCode) {
            $programId = Program::where('code', $programCode)->value('id');

            $this->assertSame(
                25,
                $generatedStudents->where('program_id', $programId)->count(),
                "Expected 25 generated students for {$programCode}."
            );
        }

        foreach ([1, 2, 3, 4] as $yearLevel) {
            $this->assertSame(
                25,
                $generatedStudents->where('year_level', $yearLevel)->count(),
                "Expected 25 generated students for year level {$yearLevel}."
            );
        }

        $this->assertDatabaseHas('users', [
            'username' => '2400001',
            'name' => 'Adrian A. Abad',
            'first_name' => 'Adrian',
            'middle_initial' => 'A',
            'last_name' => 'Abad',
            'name_extension' => null,
        ]);

        $this->assertDatabaseHas('students', [
            'student_id_number' => '2400100',
            'year_level' => 4,
        ]);

        $this->assertDatabaseHas('users', [
            'username' => '2302314',
            'name' => 'John A. Doe',
            'first_name' => 'John',
            'middle_initial' => 'A',
            'last_name' => 'Doe',
            'name_extension' => null,
        ]);
    }
}
