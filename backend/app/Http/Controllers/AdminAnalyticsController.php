<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\Program;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
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
            fputcsv($output, ['Semester', $payload['selectedSemester']?->label ?? 'All semesters']);
            fputcsv($output, ['Academic Year', $payload['selectedAcademicYear'] ?: 'All academic years']);
            fputcsv($output, ['Program', $payload['selectedProgramCode'] ?: 'All programs']);
            fputcsv($output, []);

            fputcsv($output, ['Summary']);
            foreach ($payload['summary'] as $label => $value) {
                fputcsv($output, [$label, $value]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Office Performance']);
            fputcsv($output, ['Office', 'Type', 'Signed Steps', 'Average Signing Time', 'Longest Signing Time']);
            foreach ($payload['officePerformance'] as $office) {
                fputcsv($output, [
                    $office['office_label'],
                    $office['office_type'],
                    $office['signed_steps'],
                    $office['avg_signing_time_label'],
                    $office['max_signing_time_label'],
                ]);
            }

            fputcsv($output, []);
            fputcsv($output, ['Program Flow']);
            fputcsv($output, ['Program', 'Completed', 'In Progress', 'Flagged', 'Average Completion Time']);
            foreach ($payload['programPerformance'] as $program) {
                fputcsv($output, [
                    $program['program_code'],
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
        $selectedSemesterId = $request->integer('semester_id');
        $selectedAcademicYear = trim((string) $request->query('academic_year', ''));
        $selectedProgramCode = trim((string) $request->query('program_code', ''));

        $semesters = Semester::orderByDesc('is_active')->orderByDesc('created_at')->get();
        $programs = Program::orderBy('code')->get(['code', 'name']);
        $semesterIdsForAcademicYear = $selectedAcademicYear !== ''
            ? Semester::where('academic_year', $selectedAcademicYear)->pluck('id')
            : collect();
        if ($selectedAcademicYear !== '' && $semesterIdsForAcademicYear->isEmpty()) {
            $semesterIdsForAcademicYear = collect([0]);
        }

        $completionMinutes = $this->minutesBetween('created_at', 'completed_at', 'clearances');
        $signingMinutes = $this->minutesBetween('created_at', 'signed_at', 'clearance_steps');

        $completedClearanceQuery = Clearance::query()
            ->where('status', Clearance::STATUS_COMPLETED)
            ->whereNotNull('completed_at');
        $this->applyClearanceFilters($completedClearanceQuery, $selectedSemesterId, $semesterIdsForAcademicYear, $selectedProgramCode);

        $completedCount = (clone $completedClearanceQuery)->count();
        $avgCompletionMinutes = (clone $completedClearanceQuery)->avg(DB::raw($completionMinutes));

        $activeClearanceQuery = Clearance::query()
            ->whereIn('status', [Clearance::STATUS_IN_PROGRESS, Clearance::STATUS_FLAGGED]);
        $this->applyClearanceFilters($activeClearanceQuery, $selectedSemesterId, $semesterIdsForAcademicYear, $selectedProgramCode);

        $flaggedClearanceQuery = Clearance::query()->where('status', Clearance::STATUS_FLAGGED);
        $this->applyClearanceFilters($flaggedClearanceQuery, $selectedSemesterId, $semesterIdsForAcademicYear, $selectedProgramCode);

        $officeQuery = ClearanceStep::query()
            ->join('clearances', 'clearances.id', '=', 'clearance_steps.clearance_id')
            ->where('clearance_steps.status', ClearanceStep::STATUS_APPROVED)
            ->whereNotNull('clearance_steps.signed_at');
        $this->applyJoinedClearanceFilters($officeQuery, $selectedSemesterId, $semesterIdsForAcademicYear, $selectedProgramCode);

        $officePerformance = $officeQuery
            ->select([
                'clearance_steps.office_label',
                'clearance_steps.office_type',
                DB::raw('COUNT(*) as signed_steps'),
                DB::raw('AVG(' . $signingMinutes . ') as avg_signing_minutes'),
                DB::raw('MAX(' . $signingMinutes . ') as max_signing_minutes'),
            ])
            ->groupBy('clearance_steps.office_label', 'clearance_steps.office_type')
            ->orderByDesc('avg_signing_minutes')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'office_label' => $row->office_label,
                'office_type' => str($row->office_type)->replace('_', ' ')->title()->toString(),
                'signed_steps' => (int) $row->signed_steps,
                'avg_signing_minutes' => (float) $row->avg_signing_minutes,
                'max_signing_minutes' => (float) $row->max_signing_minutes,
                'avg_signing_time_label' => $this->formatMinutes($row->avg_signing_minutes),
                'max_signing_time_label' => $this->formatMinutes($row->max_signing_minutes),
            ]);

        $programQuery = Clearance::query();
        $this->applyClearanceFilters($programQuery, $selectedSemesterId, $semesterIdsForAcademicYear, $selectedProgramCode);

        $programPerformance = $programQuery
            ->select([
                'program_code',
                'program_name',
                DB::raw("SUM(CASE WHEN status = '" . Clearance::STATUS_COMPLETED . "' THEN 1 ELSE 0 END) as completed_count"),
                DB::raw("SUM(CASE WHEN status = '" . Clearance::STATUS_IN_PROGRESS . "' THEN 1 ELSE 0 END) as in_progress_count"),
                DB::raw("SUM(CASE WHEN status = '" . Clearance::STATUS_FLAGGED . "' THEN 1 ELSE 0 END) as flagged_count"),
                DB::raw('AVG(CASE WHEN completed_at IS NOT NULL THEN ' . $completionMinutes . ' ELSE NULL END) as avg_completion_minutes'),
            ])
            ->groupBy('program_code', 'program_name')
            ->orderByDesc('avg_completion_minutes')
            ->limit(12)
            ->get()
            ->map(fn ($row) => [
                'program_code' => $row->program_code,
                'program_name' => $row->program_name,
                'completed_count' => (int) $row->completed_count,
                'in_progress_count' => (int) $row->in_progress_count,
                'flagged_count' => (int) $row->flagged_count,
                'avg_completion_minutes' => $row->avg_completion_minutes !== null ? (float) $row->avg_completion_minutes : null,
                'avg_completion_time_label' => $this->formatMinutes($row->avg_completion_minutes),
            ]);

        $slowestOffice = $officePerformance->first();
        $slowestProgram = $programPerformance->first(
            fn (array $program) => $program['avg_completion_minutes'] !== null
        );

        return [
            'semesters' => $semesters,
            'programs' => $programs,
            'selectedSemesterId' => $selectedSemesterId,
            'selectedSemester' => $semesters->firstWhere('id', $selectedSemesterId),
            'selectedAcademicYear' => $selectedAcademicYear,
            'selectedProgramCode' => $selectedProgramCode,
            'academicYears' => $semesters
                ->map(fn (Semester $semester) => $semester->displayAcademicYear())
                ->filter()
                ->unique()
                ->sortDesc()
                ->values(),
            'summary' => [
                'Completed Clearances' => number_format($completedCount),
                'Average Completion Time' => $this->formatMinutes($avgCompletionMinutes),
                'Active / Waiting Clearances' => number_format((clone $activeClearanceQuery)->count()),
                'Flagged Clearances' => number_format((clone $flaggedClearanceQuery)->count()),
                'Slowest Office' => $slowestOffice
                    ? $slowestOffice['office_label'] . ' (' . $slowestOffice['avg_signing_time_label'] . ')'
                    : 'No signed office data yet',
                'Slowest Program' => $slowestProgram
                    ? $slowestProgram['program_code'] . ' (' . $slowestProgram['avg_completion_time_label'] . ')'
                    : 'No completed program data yet',
            ],
            'officePerformance' => $officePerformance,
            'programPerformance' => $programPerformance,
        ];
    }

    private function applyClearanceFilters(
        Builder $query,
        int $semesterId,
        $semesterIdsForAcademicYear,
        string $programCode,
    ): void {
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }

        if ($semesterIdsForAcademicYear->isNotEmpty()) {
            $query->whereIn('semester_id', $semesterIdsForAcademicYear);
        }

        if ($programCode !== '') {
            $query->where('program_code', $programCode);
        }
    }

    private function applyJoinedClearanceFilters(
        Builder|QueryBuilder $query,
        int $semesterId,
        $semesterIdsForAcademicYear,
        string $programCode,
    ): void {
        if ($semesterId) {
            $query->where('clearances.semester_id', $semesterId);
        }

        if ($semesterIdsForAcademicYear->isNotEmpty()) {
            $query->whereIn('clearances.semester_id', $semesterIdsForAcademicYear);
        }

        if ($programCode !== '') {
            $query->where('clearances.program_code', $programCode);
        }
    }

    private function minutesBetween(string $startColumn, string $endColumn, string $table): string
    {
        $start = $table . '.' . $startColumn;
        $end = $table . '.' . $endColumn;

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "((strftime('%s', {$end}) - strftime('%s', {$start})) / 60.0)";
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
