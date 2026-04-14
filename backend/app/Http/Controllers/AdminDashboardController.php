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
        ]);
    }
}
