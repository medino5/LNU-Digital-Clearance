<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\Student;
use Database\Seeders\UatDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UatDatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_uat_database_seeder_creates_balanced_student_roster(): void
    {
        // This seeds the base system data plus the 100 fixed UAT student accounts.
        $this->seed(UatDatabaseSeeder::class);

        // The UAT roster should add 100 students on top of the canonical regression account.
        $this->assertSame(101, Student::query()->count());
        $this->assertNotNull(Student::query()->where('student_id_number', '2302314')->first());

        $generatedStudents = Student::query()
            ->where('student_id_number', '!=', '2302314')
            ->with('program')
            ->get();

        // The generated UAT set should stay deterministic and fully balanced by program and year.
        $this->assertSame(100, $generatedStudents->count());
        $this->assertSame(
            100,
            $generatedStudents
                ->pluck('student_id_number')
                ->filter(fn (string $studentId) => str_starts_with($studentId, '2') && strlen($studentId) === 7)
                ->unique()
                ->count()
        );

        foreach (['BSIT', 'BAEL', 'BSTM', 'BSEntrep'] as $programCode) {
            $programId = Program::query()->where('code', $programCode)->value('id');

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
    }
}
