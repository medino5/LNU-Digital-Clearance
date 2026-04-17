<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $clearancesPerSemester = Semester::query()
            ->withCount('clearances')
            ->orderBy('academic_year')
            ->orderBy('label')
            ->get()
            ->map(fn (Semester $semester) => [
                'label' => $semester->label,
                'academic_year' => $semester->displayAcademicYear(),
                'count' => $semester->clearances_count,
            ]);

        $semesterChartMax = max($clearancesPerSemester->max('count') ?? 0, 1);

        $statusChart = collect([
            [
                'label' => 'In Progress',
                'status' => Clearance::STATUS_IN_PROGRESS,
                'count' => Clearance::query()
                    ->where('status', Clearance::STATUS_IN_PROGRESS)
                    ->count(),
                'color' => '#3f6ea8',
            ],
            [
                'label' => 'Flagged',
                'status' => Clearance::STATUS_FLAGGED,
                'count' => Clearance::query()
                    ->where('status', Clearance::STATUS_FLAGGED)
                    ->count(),
                'color' => '#d2a83d',
            ],
            [
                'label' => 'Completed',
                'status' => Clearance::STATUS_COMPLETED,
                'count' => Clearance::query()
                    ->where('status', Clearance::STATUS_COMPLETED)
                    ->count(),
                'color' => '#2f8f63',
            ],
        ]);

        $statusChartTotal = max($statusChart->sum('count'), 0);

        return view('admin.dashboard', [
            'programCount' => Program::query()->count(),
            'semesterCount' => Semester::query()->count(),
            'studentCount' => Student::query()->count(),
            'officeAccountCount' => OfficeAccount::query()->count(),
            'designationCount' => OfficeDesignation::query()
                ->where('is_active', true)
                ->count(),
            'completedClearanceCount' => Clearance::query()
                ->where('status', Clearance::STATUS_COMPLETED)
                ->count(),
            'clearancesPerSemester' => $clearancesPerSemester,
            'semesterChartHasData' => $clearancesPerSemester->sum('count') > 0,
            'semesterChartMax' => $semesterChartMax,
            'statusChart' => $statusChart,
            'statusChartTotal' => $statusChartTotal,
            'statusChartHasData' => $statusChartTotal > 0,
        ]);
    }
}
