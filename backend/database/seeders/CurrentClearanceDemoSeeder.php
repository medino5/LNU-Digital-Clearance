<?php

namespace Database\Seeders;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeDesignation;
use App\Models\Semester;
use App\Models\Student;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurrentClearanceDemoSeeder extends Seeder
{
    private const INITIATED_RATIO = 0.75;

    public function run(): void
    {
        Semester::query()->update(['is_active' => false]);

        $semester = Semester::query()->updateOrCreate(
            ['label' => '1st Semester 2025-2026'],
            [
                'academic_year' => '2025-2026',
                'is_active' => true,
            ],
        );

        $totalStudents = Student::query()->count();
        $targetCount = (int) floor($totalStudents * self::INITIATED_RATIO);

        if ($targetCount < 1) {
            return;
        }

        $designationMap = $this->designationMap();
        $adminId = DB::table('users')->where('username', 'mis.admin')->value('id');
        $baseStartedAt = CarbonImmutable::parse('2025-09-01 08:00:00');
        $targetStudentIds = Student::query()
            ->orderBy('student_id_number')
            ->limit($targetCount)
            ->pluck('id');

        $processed = 0;

        Student::query()
            ->with(['user', 'program'])
            ->whereIn('id', $targetStudentIds)
            ->orderBy('student_id_number')
            ->chunk(500, function (Collection $students) use (
                $semester,
                $designationMap,
                $adminId,
                $baseStartedAt,
                &$processed,
            ) {
                $clearanceRows = [];

                foreach ($students as $student) {
                    $startedAt = $baseStartedAt
                        ->addDays($processed % 84)
                        ->addMinutes(($processed * 7) % 480);

                    $clearanceRows[] = [
                        'student_id' => $student->id,
                        'semester_id' => $semester->id,
                        'status' => $this->clearanceStatusFor($processed),
                        'reference_number' => null,
                        'completed_at' => null,
                        'pdf_path' => null,
                        'student_name' => $student->displayName(),
                        'student_id_number' => $student->student_id_number,
                        'year_level' => $student->year_level,
                        'program_code' => $student->program->code,
                        'program_name' => $student->program->name,
                        'organization_name' => $student->program->org_name,
                        'semester_label' => $semester->label,
                        'created_at' => $startedAt,
                        'updated_at' => $startedAt,
                    ];

                    $processed++;
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
                        $status = $this->stepStatusFor($student->id, $stepIndex);
                        $startedAt = CarbonImmutable::parse($clearance->created_at);
                        $signedAt = $status === ClearanceStep::STATUS_AWAITING_ACTION
                            ? null
                            : $startedAt->addHours(4 + ($stepIndex * 10) + (($studentIndex + $stepIndex) % 9));

                        $stepRows[] = [
                            'clearance_id' => $clearance->id,
                            'office_designation_id' => $designation->id,
                            'status' => $status,
                            'remarks' => $status === ClearanceStep::STATUS_FLAGGED
                                ? 'Please verify the submitted clearance details.'
                                : null,
                            'signed_at' => $signedAt,
                            'office_label' => $designation->display_name,
                            'office_type' => $designation->office_type,
                            'scope_label' => $designation->scopeLabel(),
                            'created_at' => $startedAt->addHours($stepIndex),
                            'updated_at' => $signedAt ?: $startedAt->addHours($stepIndex),
                        ];

                        $stepContexts[$clearance->id . ':' . $designation->id] = [
                            'status' => $status,
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

                        $eventRows[] = [
                            'clearance_step_id' => $step->id,
                            'actor_user_id' => null,
                            'actor_role' => 'system',
                            'action' => 'generated',
                            'remarks' => null,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ];

                        if (! $context || $context['status'] === ClearanceStep::STATUS_AWAITING_ACTION) {
                            return;
                        }

                        $action = $context['status'] === ClearanceStep::STATUS_FLAGGED ? 'flagged' : 'approved';
                        $signedAt = $context['signed_at'] ?: $createdAt;

                        $eventRows[] = [
                            'clearance_step_id' => $step->id,
                            'actor_user_id' => $context['actor_user_id'],
                            'actor_role' => 'office',
                            'action' => $action,
                            'remarks' => $action === 'flagged'
                                ? 'Please verify the submitted clearance details.'
                                : 'Cleared by assigned office.',
                            'created_at' => $signedAt,
                            'updated_at' => $signedAt,
                        ];
                    });

                foreach (array_chunk($eventRows, 1000) as $chunk) {
                    DB::table('clearance_step_events')->insert($chunk);
                }
            });
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

    private function clearanceStatusFor(int $index): string
    {
        return $index % 20 === 19
            ? Clearance::STATUS_FLAGGED
            : Clearance::STATUS_IN_PROGRESS;
    }

    private function stepStatusFor(int $studentId, int $stepIndex): string
    {
        $bucket = $studentId % 20;

        if ($bucket === 19 && $stepIndex === 1) {
            return ClearanceStep::STATUS_FLAGGED;
        }

        $approvedThrough = match (true) {
            $bucket <= 3 => 3,
            $bucket <= 8 => 1,
            $bucket <= 13 => 0,
            default => -1,
        };

        return $stepIndex <= $approvedThrough
            ? ClearanceStep::STATUS_APPROVED
            : ClearanceStep::STATUS_AWAITING_ACTION;
    }
}
