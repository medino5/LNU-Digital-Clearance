<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    private const SCOPE_OVERALL = 'overall';
    private const SCOPE_SCHOOL_YEAR = 'school_year';
    private const SCOPE_SCHOOL_YEAR_SEMESTER = 'school_year_semester';

    public function index(Request $request)
    {
        return view('admin.analytics', $this->analyticsPayload($request));
    }

    public function export(Request $request)
    {
        $payload = $this->analyticsPayload($request);
        $fileName = 'clearance-analytics-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($payload) {
            $output = fopen('php://output', 'w');

            fputcsv($output, ['Digital Clearance Analytics Report']);
            fputcsv($output, ['Generated At', now()->format('Y-m-d H:i:s')]);
            fputcsv($output, ['Scope', $payload['scopeLabel']]);
            fputcsv($output, ['School Year', $payload['selectedAcademicYear'] ?: 'All school years']);
            fputcsv($output, ['Semester', $payload['selectedSemesterTerm'] ?: 'All semesters']);
            fputcsv($output, ['Program', $payload['selectedProgramCode'] ?: 'All programs']);
            fputcsv($output, []);

            fputcsv($output, ['Summary']);
            foreach ($payload['summary'] as $metric) {
                fputcsv($output, [$metric['label'], $metric['value'], $metric['detail']]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Operational Interpretation']);
            fputcsv($output, ['Completion Rate', number_format($payload['totals']['completion_rate'], 1) . '%']);
            fputcsv($output, ['Average Clearance Time', $payload['totals']['avg_completion_label']]);
            fputcsv($output, ['Pending Clearances', $payload['totals']['pending']]);
            fputcsv($output, ['Flagged Clearances', $payload['totals']['flagged']]);
            fputcsv($output, ['Recommended Review', $payload['recentInsights'][1]['body'] ?? 'No review recommendation available.']);

            fputcsv($output, []);
            fputcsv($output, ['Clearance Requests Over Time']);
            fputcsv($output, ['Period', 'Requests', 'Completed', 'Average Clearance Time']);
            foreach ($payload['requestsOverTime'] as $point) {
                fputcsv($output, [$point['label'], $point['requests'], $point['completed'], $point['avg_time_label']]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Office Performance - Requests by Signer / Office']);
            fputcsv($output, ['Office', 'Type', 'Total Steps', 'Approved', 'Pending', 'Flagged', 'Average Signing Time']);
            foreach ($payload['officePerformance'] as $office) {
                fputcsv($output, [
                    $office['office_label'],
                    $office['office_type'],
                    $office['total_steps'],
                    $office['approved_steps'],
                    $office['pending_steps'],
                    $office['flagged_steps'],
                    $office['avg_signing_time_label'],
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Office Delay Risk']);
            fputcsv($output, ['Office', 'Delay Risk Score', 'Waiting Steps', 'Flagged Steps', 'Average Signing Time']);
            foreach ($payload['bottleneckSigners'] as $office) {
                fputcsv($output, [
                    $office['office_label'],
                    $office['delay_score'] ?? 0,
                    $office['pending_steps'],
                    $office['flagged_steps'],
                    $office['avg_signing_time_label'],
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Program Flow']);
            fputcsv($output, ['Program', 'Program Name', 'Requests', 'Completed', 'In Progress', 'Flagged', 'Average Completion Time']);
            foreach ($payload['programPerformance'] as $program) {
                fputcsv($output, [
                    $program['program_code'],
                    $program['program_name'],
                    $program['total_count'],
                    $program['completed_count'],
                    $program['in_progress_count'],
                    $program['flagged_count'],
                    $program['avg_completion_time_label'],
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsPayload(Request $request): array
    {
        $cacheKey = 'admin.analytics.payload.' . md5(json_encode([
            'scope' => $request->query('scope'),
            'semester_id' => $request->query('semester_id'),
            'academic_year' => $request->query('academic_year'),
            'semester_term' => $request->query('semester_term'),
            'semester' => $request->query('semester'),
            'program_code' => $request->query('program_code'),
        ]));

        return Cache::remember($cacheKey, now()->addSeconds(120), function () use ($request) {
            return $this->analyticsPayloadUncached($request);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function analyticsPayloadUncached(Request $request): array
    {
        $legacySemester = $request->filled('semester_id')
            ? Semester::find((int) $request->query('semester_id'))
            : null;
        $scope = $this->normalizeScope(
            $request->query('scope', self::SCOPE_SCHOOL_YEAR_SEMESTER),
        );
        $academicYears = $this->academicYearOptions();
        $selectedAcademicYear = $this->normalizeAcademicYear(
            $request->query('academic_year', $legacySemester?->displayAcademicYear()),
            $academicYears,
        );
        $selectedSemesterTerm = $this->normalizeSemesterTerm(
            $request->query(
                'semester_term',
                $request->query('semester', $legacySemester ? $this->semesterTermFromLabel($legacySemester->label) : null),
            ),
        );
        $selectedProgramCode = trim((string) $request->query('program_code', ''));
        $semesterIds = $legacySemester !== null
            ? collect([$legacySemester->id])
            : $this->semesterIdsForScope($scope, $selectedAcademicYear, $selectedSemesterTerm);

        $clearanceQuery = Clearance::query()->with('semester');
        $this->applyClearanceFilters($clearanceQuery, $semesterIds, $selectedProgramCode);
        $clearances = $clearanceQuery
            ->oldest('created_at')
            ->get();

        $totalRequests = $clearances->count();
        $completedCount = $clearances->where('status', Clearance::STATUS_COMPLETED)->count();
        $flaggedCount = $clearances->where('status', Clearance::STATUS_FLAGGED)->count();
        $pendingCount = $clearances->whereIn('status', [
            Clearance::STATUS_IN_PROGRESS,
            Clearance::STATUS_FLAGGED,
        ])->count();
        $completionRate = $totalRequests > 0 ? ($completedCount / $totalRequests) * 100 : 0;
        $avgCompletionMinutes = $this->averageCompletionMinutes($clearances);
        $totalStudents = $scope === self::SCOPE_OVERALL && $selectedProgramCode === ''
            ? Student::query()->count()
            : $clearances->pluck('student_id')->filter()->unique()->count();

        $officePerformance = $this->officePerformance($semesterIds, $selectedProgramCode);
        $programPerformance = $this->programPerformance($semesterIds, $selectedProgramCode);
        $requestsOverTime = $this->requestsOverTime($clearances, $scope);
        $statusDistribution = $this->statusDistribution($completedCount, $pendingCount - $flaggedCount, $flaggedCount);
        $bottleneckSigners = $officePerformance
            ->sortByDesc(fn (array $office) => ($office['pending_steps'] * 3) + ($office['flagged_steps'] * 4) + ($office['avg_signing_minutes'] / 240))
            ->take(5)
            ->map(function (array $office) {
                $office['delay_score'] = round(
                    ($office['pending_steps'] * 3)
                    + ($office['flagged_steps'] * 4)
                    + ($office['avg_signing_minutes'] / 240),
                    1,
                );

                return $office;
            })
            ->values();

        $highestPendingOffice = $officePerformance
            ->sortByDesc('pending_steps')
            ->first();
        $actionableBottleneckOffice = $officePerformance
            ->reject(fn (array $office) => str($office['office_label'])->lower()->contains(['vpsd', 'vice president']))
            ->sortByDesc(fn (array $office) => ($office['pending_steps'] * 3) + ($office['flagged_steps'] * 4) + ($office['avg_signing_minutes'] / 480))
            ->first() ?? $highestPendingOffice;

        return [
            'selectedScope' => $scope,
            'scopeOptions' => [
                self::SCOPE_OVERALL => 'Overall',
                self::SCOPE_SCHOOL_YEAR => 'School Year',
                self::SCOPE_SCHOOL_YEAR_SEMESTER => 'School Year + Semester',
            ],
            'scopeLabel' => $this->scopeLabel($scope, $selectedAcademicYear, $selectedSemesterTerm),
            'academicYears' => $academicYears,
            'selectedAcademicYear' => $selectedAcademicYear,
            'semesterTerms' => $this->semesterTermOptions(),
            'selectedSemesterTerm' => $selectedSemesterTerm,
            'selectedProgramCode' => $selectedProgramCode,
            'programs' => Program::orderBy('code')->get(['code', 'name']),
            'summary' => [
                [
                    'label' => 'Total Students',
                    'value' => number_format($totalStudents),
                    'detail' => $scope === self::SCOPE_OVERALL ? 'Students in the system' : 'Students with clearance activity',
                    'icon' => 'students',
                    'tone' => 'blue',
                ],
                [
                    'label' => 'Clearance Requests',
                    'value' => number_format($totalRequests),
                    'detail' => 'Requests in selected scope',
                    'icon' => 'requests',
                    'tone' => 'green',
                ],
                [
                    'label' => 'Completed Clearances',
                    'value' => number_format($completedCount),
                    'detail' => number_format($completionRate, 1) . '% completion rate',
                    'icon' => 'completed',
                    'tone' => 'purple',
                ],
                [
                    'label' => 'Pending Clearances',
                    'value' => number_format($pendingCount),
                    'detail' => $flaggedCount . ' currently flagged',
                    'icon' => 'pending',
                    'tone' => 'orange',
                ],
            ],
            'totals' => [
                'students' => $totalStudents,
                'requests' => $totalRequests,
                'completed' => $completedCount,
                'pending' => $pendingCount,
                'flagged' => $flaggedCount,
                'completion_rate' => $completionRate,
                'avg_completion_minutes' => $avgCompletionMinutes,
                'avg_completion_label' => $this->formatMinutes($avgCompletionMinutes),
            ],
            'requestsOverTime' => $requestsOverTime,
            'statusDistribution' => $statusDistribution,
            'officePerformance' => $officePerformance->take(8)->values(),
            'programPerformance' => $programPerformance,
            'bottleneckSigners' => $bottleneckSigners,
            'recentInsights' => $this->recentInsights(
                $completionRate,
                $avgCompletionMinutes,
                $actionableBottleneckOffice,
                $highestPendingOffice,
                $programPerformance->first(),
            ),
        ];
    }

    private function normalizeScope(mixed $scope): string
    {
        return in_array($scope, [
            self::SCOPE_OVERALL,
            self::SCOPE_SCHOOL_YEAR,
            self::SCOPE_SCHOOL_YEAR_SEMESTER,
        ], true) ? (string) $scope : self::SCOPE_OVERALL;
    }

    private function academicYearOptions(): Collection
    {
        return Semester::query()
            ->get()
            ->map(fn (Semester $semester) => $semester->displayAcademicYear())
            ->merge(['2023-2024', '2024-2025', '2025-2026'])
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();
    }

    private function normalizeAcademicYear(mixed $academicYear, Collection $academicYears): string
    {
        $academicYear = trim((string) $academicYear);

        if ($academicYear !== '' && $academicYears->contains($academicYear)) {
            return $academicYear;
        }

        return (string) ($academicYears->first() ?? '2025-2026');
    }

    private function normalizeSemesterTerm(mixed $semester): string
    {
        $semester = trim((string) $semester);

        return in_array($semester, array_keys($this->semesterTermOptions()), true)
            ? $semester
            : '1st Semester';
    }

    /**
     * @return array<string, string>
     */
    private function semesterTermOptions(): array
    {
        return [
            '1st Semester' => '1st Semester',
            '2nd Semester' => '2nd Semester',
            'Midyear' => 'Midyear',
        ];
    }

    private function semesterIdsForScope(string $scope, string $academicYear, string $semesterTerm): ?Collection
    {
        if ($scope === self::SCOPE_OVERALL) {
            return null;
        }

        $query = Semester::query()
            ->where(function ($query) use ($academicYear) {
                $query->where('academic_year', $academicYear)
                    ->orWhere('label', 'like', '%' . $academicYear . '%');
            });

        if ($scope === self::SCOPE_SCHOOL_YEAR_SEMESTER) {
            $query->where('label', 'like', $semesterTerm . '%');
        }

        $ids = $query->pluck('id');

        return $ids->isEmpty() ? collect([0]) : $ids;
    }

    private function applyClearanceFilters(Builder $query, ?Collection $semesterIds, string $programCode): void
    {
        if ($semesterIds !== null) {
            $query->whereIn('semester_id', $semesterIds);
        }

        if ($programCode !== '') {
            $query->where('program_code', $programCode);
        }
    }

    private function applyJoinedClearanceFilters($query, ?Collection $semesterIds, string $programCode): void
    {
        if ($semesterIds !== null) {
            $query->whereIn('clearances.semester_id', $semesterIds);
        }

        if ($programCode !== '') {
            $query->where('clearances.program_code', $programCode);
        }
    }

    private function officePerformance(?Collection $semesterIds, string $programCode): Collection
    {
        $signingMinutes = $this->minutesBetween('created_at', 'signed_at', 'clearance_steps');
        $query = ClearanceStep::query()
            ->join('clearances', 'clearances.id', '=', 'clearance_steps.clearance_id');
        $this->applyJoinedClearanceFilters($query, $semesterIds, $programCode);

        return $query
            ->select([
                'clearance_steps.office_label',
                'clearance_steps.office_type',
                DB::raw('COUNT(*) as total_steps'),
                DB::raw("SUM(CASE WHEN clearance_steps.status = '" . ClearanceStep::STATUS_APPROVED . "' THEN 1 ELSE 0 END) as approved_steps"),
                DB::raw("SUM(CASE WHEN clearance_steps.status = '" . ClearanceStep::STATUS_AWAITING_ACTION . "' THEN 1 ELSE 0 END) as pending_steps"),
                DB::raw("SUM(CASE WHEN clearance_steps.status = '" . ClearanceStep::STATUS_FLAGGED . "' THEN 1 ELSE 0 END) as flagged_steps"),
                DB::raw('AVG(CASE WHEN clearance_steps.signed_at IS NOT NULL THEN ' . $signingMinutes . ' ELSE NULL END) as avg_signing_minutes'),
            ])
            ->groupBy('clearance_steps.office_label', 'clearance_steps.office_type')
            ->orderByDesc('total_steps')
            ->get()
            ->map(fn ($row) => [
                'office_label' => $row->office_label ?: 'Office Step',
                'office_type' => str($row->office_type ?: 'office')->replace('_', ' ')->title()->toString(),
                'total_steps' => (int) $row->total_steps,
                'approved_steps' => (int) $row->approved_steps,
                'pending_steps' => (int) $row->pending_steps,
                'flagged_steps' => (int) $row->flagged_steps,
                'avg_signing_minutes' => $row->avg_signing_minutes !== null ? (float) $row->avg_signing_minutes : 0.0,
                'avg_signing_time_label' => $this->formatMinutes($row->avg_signing_minutes),
            ]);
    }

    private function programPerformance(?Collection $semesterIds, string $programCode): Collection
    {
        $completionMinutes = $this->minutesBetween('created_at', 'completed_at', 'clearances');
        $query = Clearance::query();
        $this->applyClearanceFilters($query, $semesterIds, $programCode);

        return $query
            ->select([
                'program_code',
                'program_name',
                DB::raw('COUNT(*) as total_count'),
                DB::raw("SUM(CASE WHEN status = '" . Clearance::STATUS_COMPLETED . "' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw("SUM(CASE WHEN status = '" . Clearance::STATUS_IN_PROGRESS . "' THEN 1 ELSE 0 END) as in_progress_count"),
                DB::raw("SUM(CASE WHEN status = '" . Clearance::STATUS_FLAGGED . "' THEN 1 ELSE 0 END) as flagged_count"),
                DB::raw('AVG(CASE WHEN completed_at IS NOT NULL THEN ' . $completionMinutes . ' ELSE NULL END) as avg_completion_minutes'),
            ])
            ->groupBy('program_code', 'program_name')
            ->orderByDesc('total_count')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'program_code' => $row->program_code ?: 'N/A',
                'program_name' => $row->program_name ?: 'Program not captured',
                'total_count' => (int) $row->total_count,
                'completed_count' => (int) $row->completed_count,
                'in_progress_count' => (int) $row->in_progress_count,
                'flagged_count' => (int) $row->flagged_count,
                'avg_completion_minutes' => $row->avg_completion_minutes !== null ? (float) $row->avg_completion_minutes : null,
                'avg_completion_time_label' => $this->formatMinutes($row->avg_completion_minutes),
            ]);
    }

    private function requestsOverTime(Collection $clearances, string $scope): Collection
    {
        return $clearances
            ->groupBy(fn (Clearance $clearance) => $this->timelineKey($clearance, $scope))
            ->map(function (Collection $items, string $key) {
                $completed = $items->where('status', Clearance::STATUS_COMPLETED);

                return [
                    'label' => $this->timelineLabel($key),
                    'sort' => $this->timelineSort($key),
                    'requests' => $items->count(),
                    'completed' => $completed->count(),
                    'avg_minutes' => $this->averageCompletionMinutes($completed),
                    'avg_time_label' => $this->formatMinutes($this->averageCompletionMinutes($completed)),
                ];
            })
            ->sortBy('sort')
            ->values();
    }

    private function statusDistribution(int $completed, int $pending, int $flagged): array
    {
        $total = max($completed + $pending + $flagged, 0);

        return collect([
            ['label' => 'Completed', 'count' => $completed, 'color' => '#25b86b'],
            ['label' => 'Pending', 'count' => $pending, 'color' => '#f59f32'],
            ['label' => 'Flagged', 'count' => $flagged, 'color' => '#ef4444'],
        ])->map(function (array $item) use ($total) {
            $item['percent'] = $total > 0 ? round(($item['count'] / $total) * 100, 1) : 0;

            return $item;
        })->all();
    }

    private function recentInsights(
        float $completionRate,
        ?float $avgCompletionMinutes,
        ?array $actionableBottleneckOffice,
        ?array $highestPendingOffice,
        ?array $topProgram,
    ): array {
        return [
            [
                'title' => 'Completion Rate',
                'body' => 'Current filtered completion rate is ' . number_format($completionRate, 1) . '%.',
                'tone' => 'green',
            ],
            [
                'title' => 'Actionable Bottleneck',
                'body' => $actionableBottleneckOffice
                    ? $actionableBottleneckOffice['office_label'] . ' needs review with ' . $actionableBottleneckOffice['pending_steps'] . ' pending and ' . $actionableBottleneckOffice['flagged_steps'] . ' flagged step(s).'
                    : 'No actionable office bottleneck is visible for this filter.',
                'tone' => 'orange',
            ],
            [
                'title' => 'Pending Load',
                'body' => $highestPendingOffice && $highestPendingOffice['pending_steps'] > 0
                    ? $highestPendingOffice['office_label'] . ' has ' . $highestPendingOffice['pending_steps'] . ' pending step(s).'
                    : 'No office has a pending load in this filter.',
                'tone' => 'blue',
            ],
            [
                'title' => 'Average Time',
                'body' => 'Average clearance time is ' . $this->formatMinutes($avgCompletionMinutes) . '.',
                'tone' => 'purple',
            ],
            [
                'title' => 'Program Activity',
                'body' => $topProgram
                    ? $topProgram['program_code'] . ' has the most filtered requests with ' . number_format($topProgram['total_count']) . '.'
                    : 'No program activity is available for this filter.',
                'tone' => 'blue',
            ],
        ];
    }

    private function averageCompletionMinutes(Collection $clearances): ?float
    {
        $minutes = $clearances
            ->filter(fn (Clearance $clearance) => $clearance->created_at && $clearance->completed_at)
            ->map(fn (Clearance $clearance) => $clearance->created_at->diffInMinutes($clearance->completed_at));

        return $minutes->isEmpty() ? null : (float) $minutes->avg();
    }

    private function timelineKey(Clearance $clearance, string $scope): string
    {
        if ($scope === self::SCOPE_OVERALL) {
            return 'year:' . ($clearance->semester?->displayAcademicYear() ?: $this->academicYearFromLabel($clearance->semester_label) ?: 'Unknown');
        }

        if ($scope === self::SCOPE_SCHOOL_YEAR) {
            return 'semester:' . $this->semesterTermFromLabel($clearance->semester?->label ?? $clearance->semester_label);
        }

        return 'month:' . ($clearance->created_at?->format('Y-m') ?? 'Unknown');
    }

    private function timelineLabel(string $key): string
    {
        if (str_starts_with($key, 'year:')) {
            return substr($key, 5);
        }

        if (str_starts_with($key, 'semester:')) {
            return substr($key, 9);
        }

        if (str_starts_with($key, 'month:')) {
            $value = substr($key, 6);

            return $value !== 'Unknown'
                ? \Carbon\CarbonImmutable::createFromFormat('Y-m', $value)->format('M Y')
                : 'Unknown';
        }

        return $key;
    }

    private function timelineSort(string $key): string
    {
        if (str_starts_with($key, 'semester:')) {
            $term = substr($key, 9);

            return match ($term) {
                '1st Semester' => '1',
                '2nd Semester' => '2',
                'Midyear' => '3',
                default => '9',
            };
        }

        return $key;
    }

    private function scopeLabel(string $scope, string $academicYear, string $semesterTerm): string
    {
        return match ($scope) {
            self::SCOPE_SCHOOL_YEAR => 'School Year ' . $academicYear,
            self::SCOPE_SCHOOL_YEAR_SEMESTER => $semesterTerm . ' ' . $academicYear,
            default => 'Overall Analytics',
        };
    }

    private function semesterTermFromLabel(?string $label): string
    {
        $label = (string) $label;

        if (str_starts_with($label, '2nd Semester')) {
            return '2nd Semester';
        }

        if (str_starts_with($label, 'Midyear')) {
            return 'Midyear';
        }

        return '1st Semester';
    }

    private function academicYearFromLabel(?string $label): ?string
    {
        return preg_match('/(20\d{2}-20\d{2})/', (string) $label, $matches)
            ? $matches[1]
            : null;
    }

    private function minutesBetween(string $startColumn, string $endColumn, string $table): string
    {
        $start = $table . '.' . $startColumn;
        $end = $table . '.' . $endColumn;
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "((strftime('%s', {$end}) - strftime('%s', {$start})) / 60.0)";
        }

        if ($driver === 'pgsql') {
            return "(EXTRACT(EPOCH FROM ({$end} - {$start})) / 60.0)";
        }

        return "TIMESTAMPDIFF(MINUTE, {$start}, {$end})";
    }

    private function formatMinutes($minutes): string
    {
        if ($minutes === null || $minutes === '') {
            return 'No data yet';
        }

        $minutes = max((int) round((float) $minutes), 0);

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($hours < 24) {
            return $remainingMinutes > 0
                ? $hours . 'h ' . $remainingMinutes . 'm'
                : $hours . 'h';
        }

        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        return $remainingHours > 0
            ? $days . 'd ' . $remainingHours . 'h'
            : $days . 'd';
    }
}
