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
        $selectedAcademicYear = trim((string) $request->query('history_academic_year', ''));

        $studentSearch = trim((string) $request->query('student_search', ''));
        $studentProgramId = $request->query('student_program');
        $studentYearLevel = $request->query('student_year_level');

        $officeSearch = trim((string) $request->query('office_search', ''));
        $officeProgramId = $request->query('office_program');
        $officeType = $request->query('office_type');

        $historyQuery = Clearance::with(['steps.officeDesignation', 'student.user'])
            ->where('status', Clearance::STATUS_COMPLETED)
            ->orderByDesc('completed_at');

        if ($selectedSemesterId) {
            $historyQuery->where('semester_id', $selectedSemesterId);
        }

        if ($selectedAcademicYear !== '') {
            $historyQuery->whereHas('semester', function ($query) use ($selectedAcademicYear) {
                $query->where('academic_year', $selectedAcademicYear);
            });
        }

        $history = $historyQuery->get()->groupBy('semester_label');
        $semesters = Semester::orderByDesc('is_active')->orderByDesc('created_at')->get();
        $academicYears = $semesters
            ->map(fn (Semester $semester) => $semester->displayAcademicYear())
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

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

        $studentsQuery = Student::query()
            ->with(['user', 'program'])
            ->join('users', 'users.id', '=', 'students.user_id')
            ->select('students.*');

        if ($studentSearch !== '') {
            $studentSearchLike = '%' . $studentSearch . '%';

            $studentsQuery->where(function ($query) use ($studentSearchLike) {
                $query->where('students.student_id_number', 'like', $studentSearchLike)
                    ->orWhere('users.first_name', 'like', $studentSearchLike)
                    ->orWhere('users.last_name', 'like', $studentSearchLike)
                    ->orWhere('users.middle_initial', 'like', $studentSearchLike)
                    ->orWhereRaw(
                        "TRIM(CONCAT(users.first_name, ' ', COALESCE(CONCAT(users.middle_initial, ' '), ''), users.last_name)) like ?",
                        [$studentSearchLike]
                    )
                    ->orWhereRaw(
                        "TRIM(CONCAT(users.last_name, ', ', users.first_name, ' ', COALESCE(users.middle_initial, ''))) like ?",
                        [$studentSearchLike]
                    )
                    ->orWhereRaw(
                        "TRIM(CONCAT(users.first_name, ' ', users.last_name)) like ?",
                        [$studentSearchLike]
                    );
            });
        }

        if ($studentProgramId !== null && $studentProgramId !== '') {
            $studentsQuery->where('students.program_id', $studentProgramId);
        }

        if ($studentYearLevel !== null && $studentYearLevel !== '') {
            $studentsQuery->where('students.year_level', $studentYearLevel);
        }

        $students = $studentsQuery
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->orderBy('users.middle_initial')
            ->get();

        $officeAccountsQuery = OfficeAccount::query()
            ->with(['user', 'program']);

        if ($officeSearch !== '') {
            $officeSearchLike = '%' . $officeSearch . '%';

            $officeAccountsQuery->where(function ($query) use ($officeSearchLike, $officeSearch) {
                $query->where('display_name', 'like', $officeSearchLike)
                    ->orWhereHas('user', function ($userQuery) use ($officeSearchLike) {
                        $userQuery->where('username', 'like', $officeSearchLike);
                    })
                    ->orWhereHas('program', function ($programQuery) use ($officeSearchLike) {
                        $programQuery->where('code', 'like', $officeSearchLike)
                            ->orWhere('name', 'like', $officeSearchLike)
                            ->orWhere('org_name', 'like', $officeSearchLike);
                    });

                $normalizedSearch = strtolower($officeSearch);

                foreach (OfficeAccount::typeOptions() as $value => $label) {
                    if (str_contains(strtolower($label), $normalizedSearch) || str_contains(strtolower($value), $normalizedSearch)) {
                        $query->orWhere('office_type', $value);
                    }
                }

                if (str_contains($normalizedSearch, 'year')) {
                    preg_match('/([1-4])/', $normalizedSearch, $matches);

                    if (! empty($matches[1])) {
                        $query->orWhere('year_level', (int) $matches[1]);
                    }
                }

                if (in_array($normalizedSearch, ['university', 'university-wide', 'all'], true)) {
                    $query->orWhere(function ($scopeQuery) {
                        $scopeQuery->whereNull('program_id')
                            ->whereNull('year_level');
                    });
                }
            });
        }

        if ($officeProgramId !== null && $officeProgramId !== '') {
            if ($officeProgramId === 'university') {
                $officeAccountsQuery->whereNull('program_id');
            } else {
                $officeAccountsQuery->where('program_id', $officeProgramId);
            }
        }

        if ($officeType !== null && $officeType !== '') {
            $officeAccountsQuery->where('office_type', $officeType);
        }

        $officeAccounts = $officeAccountsQuery
            ->orderBy('display_name')
            ->get();

        return view('admin.dashboard', [
            'programs' => Program::orderBy('code')->get(),
            'semesters' => $semesters,
            'students' => $students,
            'officeAccounts' => $officeAccounts,
            'officeTypeOptions' => OfficeAccount::typeOptions(),
            'yearLevels' => [1, 2, 3, 4],
            'history' => $history,
            'selectedSemesterId' => $selectedSemesterId,
            'selectedAcademicYear' => $selectedAcademicYear,
            'academicYears' => $academicYears,
            'designations' => $designations,
            'studentNameExtensions' => User::studentNameExtensionOptions(),
            'officeTypeScopeMetadata' => OfficeAccount::scopeMetadata(),
            'studentSearch' => $studentSearch,
            'studentProgramId' => $studentProgramId,
            'studentYearLevel' => $studentYearLevel,
            'officeSearch' => $officeSearch,
            'officeProgramId' => $officeProgramId,
            'selectedOfficeType' => $officeType,
        ]);
    }
}
