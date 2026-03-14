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
            $clearance = Clearance::with(['steps.events', 'steps.officeAccount'])
                ->where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->first();
        }

        return response()->json(
            $this->payloadBuilder->build($student, $semester, $clearance)
        );
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

    public function resubmit(Request $request, ClearanceStep $step)
    {
        $student = $this->studentFromRequest($request);
        $step->loadMissing('clearance', 'events', 'officeAccount');

        try {
            $this->workflow->resubmit($step, $student);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        $clearance = $step->clearance->fresh(['semester', 'steps.events', 'steps.officeAccount']);

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

        if (!$clearance->pdf_path) {
            $clearance->update([
                'pdf_path' => $this->pdfService->generate($clearance),
            ]);
        }

        return response()->download(
            Storage::disk('local')->path($clearance->pdf_path),
            ($clearance->reference_number ?: 'clearance') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
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
