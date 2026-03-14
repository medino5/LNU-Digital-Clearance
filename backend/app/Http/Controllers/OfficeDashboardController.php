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
        $officeAccount = $request->user()->loadMissing('officeAccount.program')->officeAccount;

        if (!$officeAccount) {
            abort(404, 'Office account not found.');
        }

        $baseQuery = ClearanceStep::with(['clearance.student.user', 'clearance.student.program', 'officeAccount.program'])
            ->where('office_account_id', $officeAccount->id)
            ->orderByDesc('updated_at');

        return view('office.dashboard', [
            'officeAccount' => $officeAccount,
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
        $officeAccount = $request->user()->loadMissing('officeAccount')->officeAccount;

        if (!$officeAccount || $step->office_account_id !== $officeAccount->id) {
            abort(403, 'Unauthorized.');
        }

        $data = $request->validate([
            'action' => ['required', 'in:approve,flag'],
            'remarks' => ['nullable', 'string'],
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
