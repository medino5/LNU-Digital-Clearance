<?php

namespace Database\Seeders;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeDesignation;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class HistoricalClearanceDemoSeeder extends Seeder
{
    private const OLDER_STUDENTS_PER_YEAR_LEVEL_PER_PROGRAM = 80;
    private const RECENT_STUDENTS_PER_YEAR_LEVEL_PER_PROGRAM = 140;

    /**
     * @var array<int, array{label:string, academic_year:string, starts_at:string}>
     */
    private array $semesterData = [
        ['label' => '1st Semester 2023-2024', 'academic_year' => '2023-2024', 'starts_at' => '2023-09-04 08:00:00'],
        ['label' => '2nd Semester 2023-2024', 'academic_year' => '2023-2024', 'starts_at' => '2024-02-05 08:00:00'],
        ['label' => '1st Semester 2024-2025', 'academic_year' => '2024-2025', 'starts_at' => '2024-09-02 08:00:00'],
        ['label' => '2nd Semester 2024-2025', 'academic_year' => '2024-2025', 'starts_at' => '2025-02-03 08:00:00'],
        ['label' => '1st Semester 2025-2026', 'academic_year' => '2025-2026', 'starts_at' => '2025-09-01 08:00:00'],
    ];

    public function run(): void
    {
        $programs = Program::query()->orderBy('code')->get();
        $adminId = DB::table('users')->where('username', 'mis.admin')->value('id');
        $designationMap = $this->designationMap();

        foreach ($this->semesterData as $semesterIndex => $semesterData) {
            $semester = Semester::query()->updateOrCreate(
                ['label' => $semesterData['label']],
                ['academic_year' => $semesterData['academic_year']]
            );

            $semesterStart = CarbonImmutable::parse($semesterData['starts_at']);
            $studentsPerYearLevel = $this->studentsPerYearLevelFor($semesterIndex);

            foreach ($programs as $programIndex => $program) {
                $this->studentsForProgram($program, $studentsPerYearLevel)
                    ->chunk(500)
                    ->each(function (Collection $students) use (
                        $semester,
                        $semesterStart,
                        $semesterIndex,
                        $programIndex,
                        $designationMap,
                        $adminId,
                    ) {
                        $this->seedCompletedClearanceChunk(
                            $students,
                            $semester,
                            $semesterStart,
                            $semesterIndex,
                            $programIndex,
                            $designationMap,
                            $adminId,
                        );
                    });
            }
        }
    }

    private function studentsForProgram(Program $program, int $studentsPerYearLevel): Collection
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

    private function seedCompletedClearanceChunk(
        Collection $students,
        Semester $semester,
        CarbonImmutable $semesterStart,
        int $semesterIndex,
        int $programIndex,
        array $designationMap,
        ?int $adminId,
    ): void {
        if ($students->isEmpty()) {
            return;
        }

        $protectedStudentIds = Clearance::query()
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereNotNull('reference_number')
            ->where('reference_number', 'not like', 'CLR-%')
            ->where('reference_number', 'not like', 'DEMO-%')
            ->pluck('student_id');

        if ($protectedStudentIds->isNotEmpty()) {
            $students = $students
                ->reject(fn (Student $student) => $protectedStudentIds->contains($student->id))
                ->values();
        }

        if ($students->isEmpty()) {
            return;
        }

        $completedGeneratedStudentIds = $this->completedGeneratedStudentIds($semester, $students);

        if ($completedGeneratedStudentIds->isNotEmpty()) {
            $students = $students
                ->reject(fn (Student $student) => $completedGeneratedStudentIds->contains($student->id))
                ->values();
        }

        if ($students->isEmpty()) {
            return;
        }

        $clearanceRows = [];

        foreach ($students as $studentIndex => $student) {
            $student->loadMissing('user', 'program');
            $createdAt = $semesterStart
                ->addDays(($studentIndex * 2 + $programIndex * 5) % 92)
                ->addHours(8 + (($studentIndex + $programIndex) % 6));
            $completedAt = $createdAt->addHours(30 + (($studentIndex * 7 + $programIndex * 11 + $semesterIndex * 13) % 250));

            $clearanceRows[] = [
                'student_id' => $student->id,
                'semester_id' => $semester->id,
                'status' => Clearance::STATUS_COMPLETED,
                'reference_number' => sprintf(
                    'CLR-%s-S%d-%s',
                    str_replace('-', '', $semester->academic_year ?: $semester->displayAcademicYear()),
                    $semesterIndex + 1,
                    $student->student_id_number,
                ),
                'completed_at' => $completedAt,
                'pdf_path' => null,
                'student_name' => $student->displayName(),
                'student_id_number' => $student->student_id_number,
                'year_level' => $student->year_level,
                'program_code' => $student->program->code,
                'program_name' => $student->program->name,
                'organization_name' => $student->program->org_name,
                'semester_label' => $semester->label,
                'created_at' => $createdAt,
                'updated_at' => $completedAt,
            ];
        }

        DB::table('clearances')->upsert(
            $clearanceRows,
            ['student_id', 'semester_id'],
            [
                'status',
                'reference_number',
                'completed_at',
                'pdf_path',
                'student_name',
                'student_id_number',
                'year_level',
                'program_code',
                'program_name',
                'organization_name',
                'semester_label',
                'created_at',
                'updated_at',
            ],
        );

        $studentIds = $students->pluck('id')->all();
        $clearances = Clearance::query()
            ->where('semester_id', $semester->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');
        $clearanceIds = $clearances->pluck('id')->all();

        ClearanceStep::query()->whereIn('clearance_id', $clearanceIds)->delete();

        $stepRows = [];
        $stepContexts = [];

        foreach ($students as $studentIndex => $student) {
            $clearance = $clearances->get($student->id);

            if (! $clearance) {
                continue;
            }

            foreach ($this->designationsForStudent($student, $designationMap) as $stepIndex => $designation) {
                $stepCreatedAt = CarbonImmutable::parse($clearance->created_at)->addHours($stepIndex * 4);
                $completedAt = CarbonImmutable::parse($clearance->completed_at);
                $candidateSignedAt = $stepCreatedAt->addHours($this->signingDelayHours($designation, $semesterIndex, $programIndex, $studentIndex));
                $signedAt = $candidateSignedAt->lessThan($completedAt) ? $candidateSignedAt : $completedAt;

                $stepRows[] = [
                    'clearance_id' => $clearance->id,
                    'office_designation_id' => $designation->id,
                    'status' => ClearanceStep::STATUS_APPROVED,
                    'remarks' => 'Cleared by assigned office.',
                    'signed_at' => $signedAt,
                    'office_label' => $designation->display_name,
                    'office_type' => $designation->office_type,
                    'scope_label' => $designation->scopeLabel(),
                    'created_at' => $stepCreatedAt,
                    'updated_at' => $signedAt,
                ];

                $stepContexts[$clearance->id . ':' . $designation->id] = [
                    'signed_at' => $signedAt,
                    'actor_user_id' => $designationMap['actors'][$designation->id] ?? $adminId,
                ];
            }
        }

        foreach (array_chunk($stepRows, 1000) as $chunk) {
            DB::table('clearance_steps')->insert($chunk);
        }

        $eventRows = [];
        ClearanceStep::query()
            ->whereIn('clearance_id', $clearanceIds)
            ->get(['id', 'clearance_id', 'office_designation_id', 'created_at'])
            ->each(function (ClearanceStep $step) use (&$eventRows, $stepContexts) {
                $context = $stepContexts[$step->clearance_id . ':' . $step->office_designation_id] ?? null;
                $createdAt = CarbonImmutable::parse($step->created_at);
                $signedAt = $context['signed_at'] ?? $createdAt;

                $eventRows[] = [
                    'clearance_step_id' => $step->id,
                    'actor_user_id' => null,
                    'actor_role' => 'system',
                    'action' => 'generated',
                    'remarks' => null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
                $eventRows[] = [
                    'clearance_step_id' => $step->id,
                    'actor_user_id' => $context['actor_user_id'] ?? null,
                    'actor_role' => 'office',
                    'action' => 'approved',
                    'remarks' => 'Cleared by assigned office.',
                    'created_at' => $signedAt,
                    'updated_at' => $signedAt,
                ];
            });

        foreach (array_chunk($eventRows, 1000) as $chunk) {
            DB::table('clearance_step_events')->insert($chunk);
        }
    }

    private function completedGeneratedStudentIds(Semester $semester, Collection $students): Collection
    {
        $studentIds = $students->pluck('id');

        if ($studentIds->isEmpty()) {
            return collect();
        }

        return DB::table('clearances')
            ->join('clearance_steps', 'clearance_steps.clearance_id', '=', 'clearances.id')
            ->join('clearance_step_events', 'clearance_step_events.clearance_step_id', '=', 'clearance_steps.id')
            ->where('clearances.semester_id', $semester->id)
            ->whereIn('clearances.student_id', $studentIds)
            ->where('clearances.status', Clearance::STATUS_COMPLETED)
            ->where('clearances.reference_number', 'like', 'CLR-%')
            ->groupBy('clearances.id', 'clearances.student_id')
            ->havingRaw('COUNT(DISTINCT clearance_steps.id) >= 1')
            ->havingRaw('COUNT(clearance_step_events.id) >= 2')
            ->pluck('clearances.student_id');
    }

    /**
     * @return array<string, mixed>
     */
    private function designationMap(): array
    {
        $designations = OfficeDesignation::query()
            ->with(['program', 'activeUsers'])
            ->where('is_active', true)
            ->get();

        return [
            'program_treasurer' => $designations
                ->where('office_type', OfficeDesignation::TYPE_ACAD_ORG_TREASURER)
                ->keyBy('program_id'),
            'program_adviser' => $designations
                ->where('office_type', OfficeDesignation::TYPE_ACAD_ORG_ADVISER)
                ->keyBy('program_id'),
            'year_treasurer' => $designations
                ->where('office_type', OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER)
                ->keyBy('year_level'),
            'librarian' => $designations->firstWhere('office_type', OfficeDesignation::TYPE_LIBRARIAN),
            'vpsd' => $designations->firstWhere('office_type', OfficeDesignation::TYPE_VPSD),
            'actors' => $designations
                ->mapWithKeys(fn (OfficeDesignation $designation) => [
                    $designation->id => $designation->activeUsers->first()?->id,
                ])
                ->filter()
                ->all(),
        ];
    }

    /**
     * @return array<int, OfficeDesignation>
     */
    private function designationsForStudent(Student $student, array $designationMap): array
    {
        return array_values(array_filter([
            $designationMap['program_treasurer']->get($student->program_id),
            $designationMap['year_treasurer']->get($student->year_level),
            $designationMap['program_adviser']->get($student->program_id),
            $designationMap['librarian'],
            $designationMap['vpsd'],
        ]));
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
