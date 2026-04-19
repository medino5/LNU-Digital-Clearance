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

        $this->assertSame(7, Program::query()->count());
        $this->assertSame(1, Student::count());
        $this->assertDatabaseHas('students', [
            'student_id_number' => '2302314',
        ]);
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

    public function test_uat_database_seeder_adds_a_balanced_2100_student_roster(): void
    {
        // This protects the manual-testing dataset: UAT should always reseed
        // with 300 deterministic student accounts per program plus the demo student.
        $this->seed(UatDatabaseSeeder::class);

        $this->assertSame(2101, Student::query()->count());
        $this->assertNotNull(Student::query()->where('student_id_number', '2302314')->first());

        $generatedStudents = Student::query()
            ->where('student_id_number', '!=', '2302314')
            ->with(['program', 'user'])
            ->get();

        $this->assertSame(2100, $generatedStudents->count());
        $this->assertSame(
            2100,
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

        foreach (['BSIT', 'BAEL', 'BSTM', 'BSEntrep', 'AS', 'EC', 'SM'] as $programCode) {
            $programId = Program::where('code', $programCode)->value('id');

            $this->assertSame(
                300,
                $generatedStudents->where('program_id', $programId)->count(),
                "Expected 300 generated students for {$programCode}."
            );
        }

        foreach ([1, 2, 3, 4] as $yearLevel) {
            $this->assertSame(
                525,
                $generatedStudents->where('year_level', $yearLevel)->count(),
                "Expected 525 generated students for year level {$yearLevel}."
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
            'student_id_number' => '2402100',
            'year_level' => 4,
        ]);

        $this->assertDatabaseHas('students', [
            'student_id_number' => '2401201',
            'year_level' => 1,
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
    }
}
