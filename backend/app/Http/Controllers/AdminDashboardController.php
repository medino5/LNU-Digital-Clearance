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
            'activeAssignments.user.studentProfile.program',
        ])
            ->where('is_active', true)
            ->orderBy('office_type')
            ->orderBy('display_name');

        $designationCandidates = User::with([
            'officeAccount.program',
            'studentProfile.program',
        ])
            ->where('role', '!=', User::ROLE_ADMIN)
            ->where(function ($query) {
                $query->whereHas('officeAccount')
                    ->orWhereHas('studentProfile');
            })
            ->get();

        $designations = $designationQuery->get()->map(function (OfficeDesignation $designation) use ($designationCandidates) {
            $eligibleUsers = $designationCandidates
                ->filter(fn (User $user) => $designation->matchesUser($user))
                ->sortBy(function (User $user) {
                    return strtolower($user->officeAccount->display_name ?? $user->formattedName());
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
            'officeAccounts' => OfficeAccount::with(['user', 'program'])
                ->orderBy('office_type')
                ->orderBy('program_id')
                ->orderBy('year_level')
                ->orderBy('display_name')
                ->get(),
            'officeTypeOptions' => OfficeAccount::typeOptions(),
            'yearLevels' => [1, 2, 3, 4],
            'history' => $history,
            'selectedSemesterId' => $selectedSemesterId,
            'designations' => $designations,
            'studentNameExtensions' => User::studentNameExtensionOptions(),
            'officeTypeScopeMetadata' => OfficeAccount::scopeMetadata(),
        ]);
    }
}
