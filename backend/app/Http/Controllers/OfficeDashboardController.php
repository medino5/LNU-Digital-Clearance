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
        $officeDesignations = $request->user()
            ->loadMissing('activeOfficeDesignations.program')
            ->activeOfficeDesignations
            ->sortBy('display_name')
            ->values();

        if ($officeDesignations->isEmpty()) {
            abort(404, 'Office designation not found.');
        }

        $baseQuery = ClearanceStep::with([
                'clearance.student.user',
                'clearance.student.program',
                'officeDesignation.program',
            ])
            ->whereIn('office_designation_id', $officeDesignations->pluck('id'))
            ->orderByDesc('updated_at');

        return view('office.dashboard', [
            'dashboardTitle' => $officeDesignations->count() === 1
                ? $officeDesignations->first()->display_name
                : $request->user()->name,
            'officeDesignations' => $officeDesignations,
            'pendingSteps' => (clone $baseQuery)
                ->where('status', ClearanceStep::STATUS_AWAITING_ACTION)
                ->get(),
            'processedSteps' => (clone $baseQuery)
                ->whereIn('status', [ClearanceStep::STATUS_APPROVED, ClearanceStep::STATUS_FLAGGED])
                ->get(),
        ]);
    }

    public function process(Request $request, ClearanceStep $step)
    {
        $hasDesignationAccess = $request->user()
            ->activeOfficeDesignations()
            ->where('office_designations.id', $step->office_designation_id)
            ->exists();

        if (!$hasDesignationAccess) {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'action' => ['required', 'in:approve,flag'],
            'remarks' => ['nullable', 'string', 'required_if:action,flag'],
        ], [
            'remarks.required_if' => 'Flag reason is required before marking this clearance step as flagged.',
        ]);

        try {
            if ($data['action'] === 'approve') {
                $this->workflow->approve($step, $request->user(), $data['remarks'] ?? null);
            } else {
                $this->workflow->flag($step, $request->user(), $data['remarks'] ?? null);
            }
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Clearance step updated successfully.');
    }
}
