<?php

namespace App\Http\Controllers;

use App\Models\ClearanceStep;
use App\Services\ClearanceWorkflowService;
use Illuminate\Http\Request;
use RuntimeException;

class OfficeDashboardController extends Controller
{
    public function __construct(
        protected ClearanceWorkflowService $workflow,
    ) {
    }

    public function index(Request $request)
    {
        if (! $request->user()->canAccessOfficePortal()) {
            abort(403, 'Unauthorized.');
        }

        $officeDesignations = $request->user()
            ->loadMissing('activeOfficeDesignations.program')
            ->activeOfficeDesignations
            ->sortBy('display_name')
            ->values();

        $hasActiveDesignation = $officeDesignations->isNotEmpty();

        $pendingSteps = collect();
        $processedSteps = collect();

        if ($hasActiveDesignation) {
            $baseQuery = ClearanceStep::with([
                    'clearance.student.user',
                    'clearance.student.program',
                    'officeDesignation.program',
                ])
                ->whereIn('office_designation_id', $officeDesignations->pluck('id'))
                ->orderByDesc('updated_at');

            $pendingSteps = (clone $baseQuery)
                ->where('status', ClearanceStep::STATUS_AWAITING_ACTION)
                ->get();

            $processedSteps = (clone $baseQuery)
                ->whereIn('status', [
                    ClearanceStep::STATUS_APPROVED,
                    ClearanceStep::STATUS_FLAGGED,
                ])
                ->get();
        }

        return view('office.dashboard', [
            'dashboardTitle' => 'Office Dashboard',
            'hasActiveDesignation' => $hasActiveDesignation,
            'officeDesignations' => $officeDesignations,
            'pendingSteps' => $pendingSteps,
            'processedSteps' => $processedSteps,
        ]);
    }

    public function process(Request $request, ClearanceStep $step)
    {
        $redirectTo = $this->officeDashboardUrl();

        if (! $request->user()->canAccessOfficePortal()) {
            abort(403, 'Unauthorized.');
        }

        $hasDesignationAccess = $request->user()
            ->activeOfficeDesignations()
            ->where('office_designations.id', $step->office_designation_id)
            ->exists();

        if (! $hasDesignationAccess) {
            abort(403, 'Unauthorized.');
        }

        $data = $this->validateForm(
            $request,
            'officeProcess',
            [
                'action' => ['required', 'in:approve,flag'],
                'remarks' => ['nullable', 'string', 'required_if:action,flag'],
                'step_id' => ['nullable', 'integer'],
            ],
            $redirectTo,
            [
                'remarks.required_if' => 'Flag reason is required before marking this clearance step as flagged.',
            ],
        );

        try {
            if ($data['action'] === 'approve') {
                $this->workflow->approve($step, $request->user(), $data['remarks'] ?? null);
            } else {
                $this->workflow->flag($step, $request->user(), $data['remarks'] ?? null);
            }
        } catch (RuntimeException $exception) {
            return $this->redirectWithInputAndMessage(
                $request,
                $redirectTo,
                'error',
                $exception->getMessage(),
            );
        }

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Clearance step updated successfully.',
        );
    }
}
