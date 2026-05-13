<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentRegistrationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $snapshotData = $this->buildSnapshotData();
        $semesters = Cache::remember('admin.dashboard.semester-options', now()->addMinutes(5), fn () => Semester::query()
            ->orderByDesc('academic_year')
            ->orderBy('label')
            ->get(['id', 'label', 'academic_year']));

        return view('admin.dashboard', array_merge($snapshotData, [
            'academicYears' => $semesters
                ->pluck('academic_year')
                ->filter()
                ->unique()
                ->values(),
            'semesterOptions' => $semesters,
        ]));
    }

    public function snapshots(Request $request): JsonResponse
    {
        $academicYear = $request->string('academic_year')->trim()->toString();
        $semesterId = $request->integer('semester_id') ?: null;

        if ($semesterId !== null) {
            $semester = Semester::query()->find($semesterId);

            if (! $semester) {
                $semesterId = null;
            } elseif ($academicYear !== '' && $semester->academic_year !== $academicYear) {
                $semesterId = null;
            }
        }

        return response()->json($this->buildSnapshotData(
            $academicYear !== '' ? $academicYear : null,
            $semesterId,
        ));
    }

    private function buildSnapshotData(?string $academicYear = null, ?int $semesterId = null): array
    {
        $cacheKey = 'admin.dashboard.snapshots.' . md5(json_encode([
            'academic_year' => $academicYear,
            'semester_id' => $semesterId,
        ]));

        return Cache::remember($cacheKey, now()->addSeconds(90), function () use ($academicYear, $semesterId) {
            return $this->buildUncachedSnapshotData($academicYear, $semesterId);
        });
    }

    private function buildUncachedSnapshotData(?string $academicYear = null, ?int $semesterId = null): array
    {
        $isFiltered = $academicYear !== null || $semesterId !== null;

        $semesterScope = Semester::query()
            ->when($academicYear, fn ($query) => $query->where('academic_year', $academicYear))
            ->when($semesterId, fn ($query) => $query->whereKey($semesterId));

        $semesterIds = (clone $semesterScope)->pluck('id');

        $clearanceScope = Clearance::query()
            ->when($isFiltered, fn ($query) => $query->whereIn('semester_id', $semesterIds));

        $clearancesPerSemester = Semester::query()
            ->withCount('clearances')
            ->when($academicYear, fn ($query) => $query->where('academic_year', $academicYear))
            ->when($semesterId, fn ($query) => $query->whereKey($semesterId))
            ->orderBy('academic_year')
            ->orderBy('label')
            ->get()
            ->map(fn (Semester $semester) => [
                'label' => $semester->label,
                'academic_year' => $semester->displayAcademicYear(),
                'count' => $semester->clearances_count,
            ]);

        $semesterChartMax = max($clearancesPerSemester->max('count') ?? 0, 1);

        $statusCounts = (clone $clearanceScope)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusChart = collect([
            [
                'label' => 'In Progress',
                'status' => Clearance::STATUS_IN_PROGRESS,
                'count' => (int) ($statusCounts[Clearance::STATUS_IN_PROGRESS] ?? 0),
                'color' => '#3f6ea8',
            ],
            [
                'label' => 'Flagged',
                'status' => Clearance::STATUS_FLAGGED,
                'count' => (int) ($statusCounts[Clearance::STATUS_FLAGGED] ?? 0),
                'color' => '#d2a83d',
            ],
            [
                'label' => 'Completed',
                'status' => Clearance::STATUS_COMPLETED,
                'count' => (int) ($statusCounts[Clearance::STATUS_COMPLETED] ?? 0),
                'color' => '#2f8f63',
            ],
        ]);

        $statusChartTotal = max($statusChart->sum('count'), 0);
        $completedClearanceCount = (clone $clearanceScope)
            ->where('status', Clearance::STATUS_COMPLETED)
            ->count();
        $activeClearanceCount = (clone $clearanceScope)
            ->whereIn('status', [Clearance::STATUS_IN_PROGRESS, Clearance::STATUS_FLAGGED])
            ->count();
        $studentCount = $isFiltered
            ? (clone $clearanceScope)->distinct('student_id')->count('student_id')
            : Student::query()->count();
        $scopeLabel = $this->snapshotScopeLabel($academicYear, $semesterId);

        return [
            'snapshotScopeLabel' => $scopeLabel,
            'snapshotStats' => [
                'stable' => [
                    [
                        'key' => 'programs',
                        'label' => 'Programs',
                        'value' => Program::query()->count(),
                        'hint' => 'Directory',
                    ],
                    [
                        'key' => 'office_accounts',
                        'label' => 'Office Accounts',
                        'value' => OfficeAccount::query()->count(),
                        'hint' => 'Staff pool',
                    ],
                    [
                        'key' => 'routing_designations',
                        'label' => 'Routing Designations',
                        'value' => OfficeDesignation::query()->where('is_active', true)->count(),
                        'hint' => 'Active routes',
                    ],
                ],
                'activity' => [
                    [
                        'key' => 'semesters',
                        'label' => 'Semesters',
                        'value' => (clone $semesterScope)->count(),
                        'hint' => $scopeLabel,
                    ],
                    [
                        'key' => 'students',
                        'label' => $isFiltered ? 'Students With Clearances' : 'Students',
                        'value' => $studentCount,
                        'hint' => $scopeLabel,
                    ],
                    [
                        'key' => 'pending_registrations',
                        'label' => 'Pending Registrations',
                        'value' => StudentRegistrationRequest::query()
                            ->where('status', StudentRegistrationRequest::STATUS_PENDING)
                            ->count(),
                        'hint' => 'Needs review',
                    ],
                    [
                        'key' => 'completed_clearances',
                        'label' => 'Completed Clearances',
                        'value' => $completedClearanceCount,
                        'hint' => $scopeLabel,
                    ],
                    [
                        'key' => 'active_clearances',
                        'label' => 'Active Clearances',
                        'value' => $activeClearanceCount,
                        'hint' => $scopeLabel,
                    ],
                ],
            ],
            'clearancesPerSemester' => $clearancesPerSemester,
            'semesterChartHasData' => $clearancesPerSemester->sum('count') > 0,
            'semesterChartMax' => $semesterChartMax,
            'statusChart' => $statusChart,
            'statusChartTotal' => $statusChartTotal,
            'statusChartHasData' => $statusChartTotal > 0,
        ];
    }

    private function snapshotScopeLabel(?string $academicYear, ?int $semesterId): string
    {
        if ($semesterId) {
            $semester = Semester::query()->find($semesterId);

            if ($semester) {
                $academicYear = $semester->academic_year ?? '';

                if ($academicYear !== '' && ! str_contains($semester->label, $academicYear)) {
                    return trim($semester->label . ' ' . $academicYear);
                }

                return $semester->label;
            }
        }

        return $academicYear ?: 'All records';
    }
}
