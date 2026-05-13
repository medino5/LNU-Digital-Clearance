<?php

namespace Database\Seeders;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeDesignation;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HistoricalClearanceDemoSeeder extends Seeder
{
    private const OLDER_STUDENTS_PER_YEAR_LEVEL_PER_PROGRAM = 15;
    private const RECENT_STUDENTS_PER_YEAR_LEVEL_PER_PROGRAM = 40;

    /**
     * @var array<int, array{label:string, academic_year:string, starts_at:string}>
     */
    private array $semesterData = [
        ['label' => '1st Semester 2023-2024', 'academic_year' => '2023-2024', 'starts_at' => '2023-09-04 08:00:00'],
        ['label' => '2nd Semester 2023-2024', 'academic_year' => '2023-2024', 'starts_at' => '2024-02-05 08:00:00'],
        ['label' => '1st Semester 2024-2025', 'academic_year' => '2024-2025', 'starts_at' => '2024-09-02 08:00:00'],
        ['label' => '2nd Semester 2024-2025', 'academic_year' => '2024-2025', 'starts_at' => '2025-02-03 08:00:00'],
    ];

    public function run(): void
    {
        $admin = User::query()->where('username', 'mis.admin')->first();
        $programs = Program::query()->orderBy('code')->get();

        foreach ($this->semesterData as $semesterIndex => $semesterData) {
            $semester = Semester::updateOrCreate(
                ['label' => $semesterData['label']],
                ['academic_year' => $semesterData['academic_year']]
            );

            $semesterStart = CarbonImmutable::parse($semesterData['starts_at']);

            foreach ($programs as $programIndex => $program) {
                $students = $this->studentsForProgram($program, $this->studentsPerYearLevelFor($semesterIndex));

                foreach ($students as $studentIndex => $student) {
                    $this->seedCompletedClearance(
                        $student,
                        $semester,
                        $semesterStart,
                        $semesterIndex,
                        $programIndex,
                        $studentIndex,
                        $admin,
                    );
                }
            }
        }
    }

    private function studentsForProgram(Program $program, int $studentsPerYearLevel)
    {
        return Student::query()
            ->with(['user', 'program'])
            ->where('program_id', $program->id)
            ->whereIn('year_level', [1, 2, 3, 4])
            ->orderBy('year_level')
            ->orderBy('student_id_number')
            ->get()
            ->groupBy('year_level')
            ->flatMap(fn ($students) => $students->take($studentsPerYearLevel))
            ->values();
    }

    private function studentsPerYearLevelFor(int $semesterIndex): int
    {
        return $semesterIndex >= 2
            ? self::RECENT_STUDENTS_PER_YEAR_LEVEL_PER_PROGRAM
            : self::OLDER_STUDENTS_PER_YEAR_LEVEL_PER_PROGRAM;
    }

    private function seedCompletedClearance(
        Student $student,
        Semester $semester,
        CarbonImmutable $semesterStart,
        int $semesterIndex,
        int $programIndex,
        int $studentIndex,
        ?User $admin,
    ): void {
        $student->loadMissing('user', 'program');

        $referenceNumber = sprintf(
            'CLR-%s-S%d-%s',
            str_replace('-', '', $semester->academic_year ?: $semester->displayAcademicYear()),
            $semesterIndex + 1,
            $student->student_id_number,
        );

        $existing = Clearance::query()
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->first();

        if (
            $existing
            && ! str_starts_with((string) $existing->reference_number, 'DEMO-')
            && ! str_starts_with((string) $existing->reference_number, 'CLR-')
        ) {
            return;
        }

        $createdAt = $semesterStart
            ->addDays(($studentIndex * 2 + $programIndex * 5) % 92)
            ->addHours(8 + (($studentIndex + $programIndex) % 6));
        $completedAt = $createdAt->addHours(30 + (($studentIndex * 7 + $programIndex * 11 + $semesterIndex * 13) % 250));

        DB::transaction(function () use (
            $student,
            $semester,
            $referenceNumber,
            $createdAt,
            $completedAt,
            $semesterIndex,
            $programIndex,
            $studentIndex,
            $admin,
        ) {
            $clearance = Clearance::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'semester_id' => $semester->id,
                ],
                [
                    'status' => Clearance::STATUS_COMPLETED,
                    'reference_number' => $referenceNumber,
                    'completed_at' => $completedAt,
                    'pdf_path' => null,
                    'student_name' => $student->displayName(),
                    'student_id_number' => $student->student_id_number,
                    'year_level' => $student->year_level,
                    'program_code' => $student->program->code,
                    'program_name' => $student->program->name,
                    'organization_name' => $student->program->org_name,
                    'semester_label' => $semester->label,
                ]
            );

            $clearance->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $completedAt,
            ])->save();

            $clearance->steps()->delete();

            foreach ($this->designationsForStudent($student) as $stepIndex => $designation) {
                $stepCreatedAt = $createdAt->addHours($stepIndex * 4);
                $signedAt = min(
                    $completedAt,
                    $stepCreatedAt->addHours($this->signingDelayHours($designation, $semesterIndex, $programIndex, $studentIndex))
                );
                $actor = $designation->activeUsers->first() ?: $admin;

                $step = $clearance->steps()->create([
                    'office_designation_id' => $designation->id,
                    'status' => ClearanceStep::STATUS_APPROVED,
                    'remarks' => 'Approved during clearance processing.',
                    'signed_at' => $signedAt,
                    'office_label' => $designation->display_name,
                    'office_type' => $designation->office_type,
                    'scope_label' => $designation->scopeLabel(),
                ]);

                $step->forceFill([
                    'created_at' => $stepCreatedAt,
                    'updated_at' => $signedAt,
                ])->save();

                $generatedEvent = $step->events()->create([
                    'actor_role' => 'system',
                    'action' => 'generated',
                ]);
                $generatedEvent->forceFill([
                    'created_at' => $stepCreatedAt,
                    'updated_at' => $stepCreatedAt,
                ])->save();

                $approvedEvent = $step->events()->create([
                    'actor_user_id' => $actor?->id,
                    'actor_role' => $actor?->role ?? 'system',
                    'action' => 'approved',
                    'remarks' => 'Approved during clearance processing.',
                ]);
                $approvedEvent->forceFill([
                    'created_at' => $signedAt,
                    'updated_at' => $signedAt,
                ])->save();
            }
        });
    }

    /**
     * @return array<int, OfficeDesignation>
     */
    private function designationsForStudent(Student $student): array
    {
        return OfficeDesignation::query()
            ->with(['program', 'activeUsers'])
            ->where('is_active', true)
            ->forStudent($student)
            ->orderByRaw("CASE office_type
                WHEN 'acad_org_treasurer' THEN 1
                WHEN 'year_level_treasurer' THEN 2
                WHEN 'acad_org_adviser' THEN 3
                WHEN 'librarian' THEN 4
                WHEN 'vpsd' THEN 5
                ELSE 6
            END")
            ->get()
            ->unique('office_type')
            ->values()
            ->all();
    }

    private function signingDelayHours(
        OfficeDesignation $designation,
        int $semesterIndex,
        int $programIndex,
        int $studentIndex,
    ): int {
        $base = match ($designation->office_type) {
            OfficeDesignation::TYPE_ACAD_ORG_TREASURER => 10,
            OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER => 18,
            OfficeDesignation::TYPE_ACAD_ORG_ADVISER => 32,
            OfficeDesignation::TYPE_LIBRARIAN => 52,
            OfficeDesignation::TYPE_VPSD => 76,
            default => 24,
        };

        return $base + (($studentIndex * 5 + $programIndex * 9 + $semesterIndex * 7) % 72);
    }
}
