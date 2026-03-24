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

        $generatedStudents = Student::query()
            ->where('student_id_number', '!=', '2302314')
            ->with(['program', 'user'])
            ->get();

        $this->assertSame(101, Student::count());
        $this->assertSame(100, $generatedStudents->count());

        foreach ($generatedStudents as $student) {
            $this->assertMatchesRegularExpression('/^2\d{6}$/', $student->student_id_number);
            $this->assertSame($student->student_id_number, $student->user->username);
        }

        foreach (['BSIT', 'BAEL', 'BSTM', 'BSEntrep'] as $programCode) {
            $programId = Program::where('code', $programCode)->value('id');

            $this->assertSame(
                25,
                Student::where('program_id', $programId)
                    ->where('student_id_number', '!=', '2302314')
                    ->count()
            );
        }

        foreach ([1, 2, 3, 4] as $yearLevel) {
            $this->assertSame(
                25,
                Student::where('year_level', $yearLevel)
                    ->where('student_id_number', '!=', '2302314')
                    ->count()
            );
        }

        $this->assertDatabaseHas('users', [
            'username' => '2400001',
            'name' => 'Adrian A. Abad',
        ]);

        $this->assertDatabaseHas('students', [
            'student_id_number' => '2400100',
            'year_level' => 4,
        ]);
    }
}
