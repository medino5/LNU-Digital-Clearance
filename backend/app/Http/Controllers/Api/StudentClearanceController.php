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
            $clearance = Clearance::with(['steps.events', 'steps.officeDesignation'])
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
            ->with(['semester:id,label,academic_year'])
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

    protected function studentFromRequest(Request $request)
    {
        $student = $request->user()->loadMissing('studentProfile.program')->studentProfile;

        if (!$student) {
            abort(404, 'Student profile not found.');
        }

        return $student;
    }
}
