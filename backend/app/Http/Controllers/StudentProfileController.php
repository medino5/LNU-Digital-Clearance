<?php

namespace App\Http\Controllers;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\Student;
use Illuminate\Http\Request;

class StudentProfileController extends Controller
{
    public function adminShow(Request $request, Student $student)
    {
        $profile = $this->profileData($student);

        if ($request->boolean('partial')) {
            return view('students.partials.profile-panel', $profile);
        }

        return view('students.profile', $profile + [
            'backUrl' => route('admin.students.index'),
            'backLabel' => 'Back to Students',
        ]);
    }

    public function officeShow(Request $request, Student $student)
    {
        $user = $request->user();

        if (! $user?->canAccessOfficePortal()) {
            abort(403, 'Unauthorized.');
        }

        $designationIds = $user->activeOfficeDesignations()->pluck('office_designations.id');

        $canView = ClearanceStep::query()
            ->whereIn('office_designation_id', $designationIds)
            ->whereHas('clearance', fn ($query) => $query->where('student_id', $student->id))
            ->exists();

        if (! $canView) {
            abort(403, 'This student is not routed to your current designation.');
        }

        $profile = $this->profileData($student);

        if ($request->boolean('partial')) {
            return view('students.partials.profile-panel', $profile);
        }

        return view('students.profile', $profile + [
            'backUrl' => route('office.dashboard'),
            'backLabel' => 'Back to Office Dashboard',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function profileData(Student $student): array
    {
        $student->loadMissing(['user', 'program']);

        $clearances = Clearance::query()
            ->select([
                'id',
                'student_id',
                'semester_id',
                'status',
                'reference_number',
                'completed_at',
                'semester_label',
                'created_at',
            ])
            ->with([
                'semester:id,label,academic_year',
                'steps' => fn ($query) => $query
                    ->select([
                        'id',
                        'clearance_id',
                        'office_designation_id',
                        'status',
                        'remarks',
                        'signed_at',
                        'office_label',
                        'office_type',
                        'scope_label',
                    ])
                    ->with([
                        'officeDesignation:id,display_name,office_type,program_id',
                        'latestEvent' => fn ($eventQuery) => $eventQuery
                            ->select([
                                'clearance_step_events.id',
                                'clearance_step_events.clearance_step_id',
                                'clearance_step_events.actor_user_id',
                                'clearance_step_events.actor_role',
                                'clearance_step_events.action',
                                'clearance_step_events.remarks',
                                'clearance_step_events.created_at',
                            ])
                            ->with('actor:id,name,first_name,middle_initial,last_name,name_extension,role,is_student'),
                    ]),
            ])
            ->where('student_id', $student->id)
            ->latest('created_at')
            ->get();

        $currentClearance = $clearances->first();
        $currentSteps = $currentClearance?->steps ?? collect();
        $stepTotal = $currentSteps->count();
        $approvedSteps = $currentSteps->where('status', ClearanceStep::STATUS_APPROVED)->count();

        return [
            'student' => $student,
            'clearances' => $clearances,
            'currentClearance' => $currentClearance,
            'progressPercent' => $stepTotal > 0 ? (int) round(($approvedSteps / $stepTotal) * 100) : 0,
            'stepCounts' => [
                'total' => $stepTotal,
                'approved' => $approvedSteps,
                'flagged' => $currentSteps->where('status', ClearanceStep::STATUS_FLAGGED)->count(),
                'awaiting' => $currentSteps->where('status', ClearanceStep::STATUS_AWAITING_ACTION)->count(),
            ],
        ];
    }
}
