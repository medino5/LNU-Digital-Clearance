<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\Semester;
use App\Services\ClearancePdfService;
use App\Services\ClearanceWorkflowService;
use App\Support\StudentClearancePayloadBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class StudentClearanceController extends Controller
{
    public function __construct(
        protected ClearanceWorkflowService $workflow,
        protected StudentClearancePayloadBuilder $payloadBuilder,
        protected ClearancePdfService $pdfService,
    ) {
    }

    public function current(Request $request)
    {
        $student = $this->studentFromRequest($request);
        $semester = Semester::active()->first();
        $clearance = null;

        if ($semester) {
            $clearance = Clearance::with(['steps.events', 'steps.officeDesignation.activeUsers'])
                ->where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->first();
        }

        return response()->json(
            $this->payloadBuilder->build($student, $semester, $clearance)
        );
    }

    public function history(Request $request)
    {
        $student = $this->studentFromRequest($request);

        $clearances = Clearance::query()
            ->select([
                'id',
                'student_id',
                'semester_id',
                'status',
                'reference_number',
                'completed_at',
                'created_at',
                'semester_label',
                'program_code',
                'program_name',
                'year_level',
            ])
            ->with([
                'semester:id,label,academic_year',
                'steps:id,clearance_id,status,remarks,signed_at,office_label,office_type,scope_label',
                'steps.latestEvent.actor:id,name,first_name,middle_initial,last_name,name_extension,username,is_student,role',
                'steps.officeDesignation.activeUsers:id,name,first_name,middle_initial,last_name,name_extension,username,profile_photo_path,is_student,role',
            ])
            ->withCount([
                'steps as total_steps_count',
                'steps as approved_steps_count' => fn ($query) => $query->where('status', 'approved'),
                'steps as flagged_steps_count' => fn ($query) => $query->where('status', 'flagged'),
                'steps as awaiting_steps_count' => fn ($query) => $query->where('status', 'awaiting_action'),
            ])
            ->where('student_id', $student->id)
            ->orderByDesc('completed_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'history' => $clearances->map(fn (Clearance $clearance) => [
                'id' => $clearance->id,
                'status' => $clearance->status,
                'reference_number' => $clearance->reference_number,
                'semester_label' => $clearance->semester_label ?: $clearance->semester?->label,
                'academic_year' => $clearance->semester?->displayAcademicYear(),
                'program_code' => $clearance->program_code,
                'program_name' => $clearance->program_name,
                'year_level' => $clearance->year_level,
                'completed_at' => $clearance->completed_at?->toISOString(),
                'started_at' => $clearance->created_at?->toISOString(),
                'counts' => [
                    'total' => (int) $clearance->total_steps_count,
                    'approved' => (int) $clearance->approved_steps_count,
                    'flagged' => (int) $clearance->flagged_steps_count,
                    'awaiting_action' => (int) $clearance->awaiting_steps_count,
                ],
                'steps' => $clearance->steps->map(fn (ClearanceStep $step) => [
                    'office_label' => $step->office_label,
                    'office_type' => $step->office_type,
                    'scope_label' => $step->scope_label,
                    'status' => $step->status,
                    'remarks' => $step->remarks,
                    'signed_at' => $step->signed_at?->toISOString(),
                    'last_action' => $step->latestEvent?->action,
                    'last_action_at' => $step->latestEvent?->created_at?->toISOString(),
                    'signed_by' => $step->latestEvent?->actor?->formattedName(),
                    'signed_by_profile_photo_url' => $step->latestEvent?->actor?->profilePhotoUrl(),
                    'assigned_officer' => ($assignedOfficer = $step->officeDesignation?->activeUsers?->first()) ? [
                        'name' => $assignedOfficer->formattedName(),
                        'profile_photo_url' => $assignedOfficer->profilePhotoUrl(),
                    ] : null,
                ])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $student = $this->studentFromRequest($request);

        try {
            $clearance = $this->workflow->createOrResume($student);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json(
            $this->payloadBuilder->build($student, $clearance->semester, $clearance)
        );
    }

    public function cancelCurrent(Request $request)
    {
        $student = $this->studentFromRequest($request);
        $semester = $this->workflow->activeSemester();

        $clearance = Clearance::with('steps.events')
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->first();

        if (!$clearance) {
            return response()->json([
                'message' => 'No active clearance record found to cancel.',
            ], 404);
        }

        if (!$this->canCancelClearance($clearance)) {
            return response()->json([
                'message' => 'This clearance can no longer be cancelled because an office has already acted on it.',
            ], 422);
        }

        DB::transaction(function () use ($clearance) {
            if ($clearance->pdf_path) {
                Storage::disk('local')->delete($clearance->pdf_path);
            }

            $clearance->delete();
        });

        return response()->json([
            'message' => 'Clearance cancelled. Update your profile if needed, then start again.',
            ...$this->payloadBuilder->build($student->fresh(['user', 'program']), $semester, null),
        ]);
    }

    public function resubmit(Request $request, ClearanceStep $step)
    {
        $student = $this->studentFromRequest($request);
        $step->loadMissing('clearance', 'events', 'officeDesignation');

        try {
            $this->workflow->resubmit($step, $student);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $clearance = $step->clearance->fresh(['semester', 'steps.events', 'steps.officeDesignation']);

        return response()->json(
            $this->payloadBuilder->build($student, $clearance->semester, $clearance)
        );
    }

    public function downloadCurrent(Request $request)
    {
        $student = $this->studentFromRequest($request);
        $semester = $this->workflow->activeSemester();

        $clearance = Clearance::with('steps')
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->first();

        if (!$clearance) {
            return response()->json([
                'message' => 'No clearance record found for the active semester.',
            ], 404);
        }

        if ($clearance->status !== Clearance::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Your clearance is not completed yet.',
            ], 422);
        }

        $path = $this->resolvePdf($clearance);

        return response()->download(
            Storage::disk('local')->path($path),
            ($clearance->reference_number ?: 'clearance-' . $clearance->id) . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    public function downloadHistory(Request $request, Clearance $clearance)
    {
        $student = $this->studentFromRequest($request);

        if ($clearance->student_id !== $student->id) {
            abort(403, 'You are not allowed to download this clearance.');
        }

        if ($clearance->status !== Clearance::STATUS_COMPLETED) {
            abort_if(
                $clearance->status !== Clearance::STATUS_COMPLETED,
                403,
                'Only completed clearances can be downloaded.'
            );
        }

        $path = $this->resolvePdf($clearance);

        return response()->download(
            Storage::disk('local')->path($path),
            ($clearance->reference_number ?: 'clearance-' . $clearance->id) . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    private function resolvePdf(Clearance $clearance): string
    {
        if (
            !$clearance->pdf_path ||
            !Storage::disk('local')->exists($clearance->pdf_path)
        ) {
            $clearance->update([
                'pdf_path' => $this->pdfService->generate($clearance),
            ]);
        }

        return $clearance->pdf_path;
    }

    private function canCancelClearance(Clearance $clearance): bool
    {
        if ($clearance->status !== Clearance::STATUS_IN_PROGRESS) {
            return false;
        }

        return $clearance->steps->every(function (ClearanceStep $step) {
            return $step->status === ClearanceStep::STATUS_AWAITING_ACTION
                && $step->events->every(fn ($event) => $event->action === 'generated');
        });
    }

    protected function studentFromRequest(Request $request)
    {
        $student = $request->user()->loadMissing('studentProfile.program')->studentProfile;

        if (!$student) {
            abort(404, 'Student profile not found.');
        }

        return $student;
    }
}
