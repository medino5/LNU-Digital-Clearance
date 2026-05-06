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

        return view('students.profile', $this->profileData($student) + [
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

        $clearances = Clearance::with([
            'semester',
            'steps.officeDesignation.program',
            'steps.events.actor',
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
