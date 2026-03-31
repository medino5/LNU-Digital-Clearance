<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedSemesterId = $request->integer('history_semester');

        $historyQuery = Clearance::with(['steps.officeDesignation', 'student.user'])
            ->where('status', Clearance::STATUS_COMPLETED)
            ->orderByDesc('completed_at');

        if ($selectedSemesterId) {
            $historyQuery->where('semester_id', $selectedSemesterId);
        }

        $history = $historyQuery->get()->groupBy('semester_label');

        $designationQuery = OfficeDesignation::with([
            'program',
            'activeAssignments.user.officeAccount.program',
        ])
            ->where('is_active', true)
            ->orderBy('office_type')
            ->orderBy('display_name');

        $officeUsers = User::with(['officeAccount.program'])
            ->where('role', User::ROLE_OFFICE)
            ->whereHas('officeAccount')
            ->get();

        $designations = $designationQuery->get()->map(function (OfficeDesignation $designation) use ($officeUsers) {
            $eligibleUsers = $officeUsers
                ->filter(fn (User $user) => $designation->matchesOfficeAccount($user->officeAccount))
                ->sortBy(function (User $user) {
                    return strtolower($user->officeAccount->display_name ?? $user->name);
                })
                ->values();

            $currentAssignment = $designation->activeAssignments->first();

            $designation->setRelation('eligible_users', $eligibleUsers);
            $designation->setRelation('current_assignment', $currentAssignment);

            return $designation;
        });

        return view('admin.dashboard', [
            'programs' => Program::orderBy('code')->get(),
            'semesters' => Semester::orderByDesc('is_active')->orderByDesc('created_at')->get(),
            'students' => Student::with(['user', 'program'])->orderBy('student_id_number')->get(),
            'officeAccounts' => OfficeAccount::with(['user', 'program'])->orderBy('display_name')->get(),
            'officeTypeOptions' => OfficeAccount::typeOptions(),
            'yearLevels' => [1, 2, 3, 4],
            'history' => $history,
            'selectedSemesterId' => $selectedSemesterId,
            'designations' => $designations,
            'studentNameExtensions' => User::studentNameExtensionOptions(),
        ]);
    }
}
