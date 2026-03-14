<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\OfficeAccount;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedSemesterId = $request->integer('history_semester');

        $historyQuery = Clearance::with(['steps.officeAccount', 'student.user'])
            ->where('status', Clearance::STATUS_COMPLETED)
            ->orderByDesc('completed_at');

        if ($selectedSemesterId) {
            $historyQuery->where('semester_id', $selectedSemesterId);
        }

        $history = $historyQuery->get()->groupBy('semester_label');

        return view('admin.dashboard', [
            'programs' => Program::orderBy('code')->get(),
            'semesters' => Semester::orderByDesc('is_active')->orderByDesc('created_at')->get(),
            'students' => Student::with(['user', 'program'])->orderBy('student_id_number')->get(),
            'officeAccounts' => OfficeAccount::with(['user', 'program'])->orderBy('display_name')->get(),
            'officeTypeOptions' => OfficeAccount::typeOptions(),
            'yearLevels' => [1, 2, 3, 4],
            'history' => $history,
            'selectedSemesterId' => $selectedSemesterId,
        ]);
    }
}
