<?php

namespace App\Http\Controllers;

use App\Models\ClearanceStep;
use App\Services\ClearanceWorkflowService;
use Illuminate\Database\QueryException;
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

        $request->validate([
            'archive_search' => ['nullable', 'string', 'max:120'],
            'archive_status' => ['nullable', 'in:' . ClearanceStep::STATUS_APPROVED . ',' . ClearanceStep::STATUS_FLAGGED],
            'archive_sort' => ['nullable', 'in:processed_desc,processed_asc,student_asc,student_id_asc,program_asc'],
        ], [
            'archive_search.max' => 'Archive search must be 120 characters or fewer.',
        ]);

        $tab = $request->query('tab') === 'archive' ? 'archive' : 'active';
        $archiveSearch = trim((string) $request->query('archive_search', ''));
        $archiveStatus = in_array($request->query('archive_status'), [
            ClearanceStep::STATUS_APPROVED,
            ClearanceStep::STATUS_FLAGGED,
        ], true) ? $request->query('archive_status') : '';
        $archiveSort = in_array($request->query('archive_sort'), [
            'processed_desc',
            'processed_asc',
            'student_asc',
            'student_id_asc',
            'program_asc',
        ], true) ? $request->query('archive_sort') : 'processed_desc';

        $officeDesignations = $request->user()
            ->loadMissing('activeOfficeDesignations.program')
            ->activeOfficeDesignations
            ->sortBy('display_name')
            ->values();

        $hasActiveDesignation = $officeDesignations->isNotEmpty();

        $pendingSteps = collect();
        $archiveSteps = collect();
        $pendingCount = 0;
        $archiveCount = 'History';

        if ($hasActiveDesignation) {
            $baseQuery = ClearanceStep::query()
                ->select('clearance_steps.*')
                ->with([
                    'clearance.student.user',
                    'clearance.student.program',
                    'officeDesignation.program',
                ])
                ->whereIn('clearance_steps.office_designation_id', $officeDesignations->pluck('id'));

            $pendingCount = (clone $baseQuery)
                ->where('clearance_steps.status', ClearanceStep::STATUS_AWAITING_ACTION)
                ->count();

            if ($tab === 'active') {
                $pendingSteps = (clone $baseQuery)
                    ->where('clearance_steps.status', ClearanceStep::STATUS_AWAITING_ACTION)
                    ->orderByDesc('clearance_steps.updated_at')
                    ->simplePaginate(20, ['*'], 'pending_page')
                    ->withQueryString();
            } else {
                $archiveQuery = $this->applyArchiveFilters(
                    $this->archiveQuery($officeDesignations->pluck('id')),
                    $archiveSearch,
                    $archiveStatus,
                );

                $archiveSteps = $this->applyArchiveSort($archiveQuery, $archiveSort)
                    ->simplePaginate(20, ['*'], 'archive_page')
                    ->withQueryString();
            }
        }

        return view('office.dashboard', [
            'dashboardTitle' => 'Office Dashboard',
            'hasActiveDesignation' => $hasActiveDesignation,
            'officeDesignations' => $officeDesignations,
            'tab' => $tab,
            'pendingSteps' => $pendingSteps,
            'archiveSteps' => $archiveSteps,
            'pendingCount' => $pendingCount,
            'archiveCount' => $archiveCount,
            'archiveSearch' => $archiveSearch,
            'archiveStatus' => $archiveStatus,
            'archiveSort' => $archiveSort,
        ]);
    }

    public function process(Request $request, ClearanceStep $step)
    {
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
                'action' => ['required', 'in:approve,flag,undo_approval,undo_flag'],
                'confirm_action' => ['nullable','string'],
                'remarks' => ['nullable', 'string', 'max:500', 'required_if:action,flag'],
                'step_id' => ['nullable', 'integer'],
                'return_tab' => ['nullable', 'in:active,archive'],
            ],
            $this->officeDashboardUrl(),
            [
                'remarks.required_if' => 'Reject reason is required before rejecting this clearance step.',
                'remarks.max' => 'Remarks must be 500 characters or fewer.',
            ],
        );

        $redirectTo = route('office.dashboard', [
            'tab' => ($data['return_tab'] ?? null) === 'archive' ? 'archive' : 'active',
        ]);

        try {
            if ($data['action'] === 'approve') {

                if (($data['confirm_action'] ?? null) !== 'approve') {
                    throw new RuntimeException('Approval not confirmed.');
                }

                $this->workflow->approve($step, $request->user(), $data['remarks'] ?? null);

            } elseif ($data['action'] === 'flag') {

                $this->workflow->flag($step, $request->user(), $data['remarks'] ?? null);

            } elseif ($data['action'] === 'undo_approval') {

                if (($data['confirm_action'] ?? null) !== 'undo_approval') {
                    throw new RuntimeException('Undo approval not confirmed.');
                }

                $this->workflow->undoApproval($step, $request->user());
            } elseif ($data['action'] === 'undo_flag') {

                if (($data['confirm_action'] ?? null) !== 'undo_flag') {
                    throw new RuntimeException('Undo rejection not confirmed.');
                }

                $this->workflow->undoFlag($step, $request->user());
            }
        } catch (RuntimeException $exception) {
            return $this->redirectWithInputAndMessage(
                $request,
                $redirectTo,
                'error',
                $exception->getMessage(),
            );
        } catch (QueryException $exception) {
            report($exception);

            return $this->redirectWithInputAndMessage(
                $request,
                $redirectTo,
                'error',
                'Unable to save this clearance action. Please run the latest database migrations and try again.',
            );
        }

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Clearance step updated successfully.',
        );
    }

    private function applyArchiveFilters($query, string $search, string $status)
    {
        if ($status !== '') {
            $query->where('clearance_steps.status', $status);
        }

        if ($search !== '') {
            $searchLike = '%' . $search . '%';

            $query->where(function ($query) use ($searchLike) {
                $query->where('clearance_steps.office_label', 'like', $searchLike)
                    ->orWhere('clearance_steps.remarks', 'like', $searchLike)
                    ->orWhere('clearances.reference_number', 'like', $searchLike)
                    ->orWhere('students.student_id_number', 'like', $searchLike)
                    ->orWhere('programs.code', 'like', $searchLike)
                    ->orWhere('programs.name', 'like', $searchLike)
                    ->orWhere('users.name', 'like', $searchLike)
                    ->orWhere('users.first_name', 'like', $searchLike)
                    ->orWhere('users.last_name', 'like', $searchLike);
            });
        }

        return $query;
    }

    private function archiveQuery($designationIds)
    {
        return ClearanceStep::query()
            ->select('clearance_steps.*')
            ->with([
                'clearance.student.user',
                'clearance.student.program',
                'officeDesignation.program',
            ])
            ->join('clearances', 'clearance_steps.clearance_id', '=', 'clearances.id')
            ->leftJoin('students', 'clearances.student_id', '=', 'students.id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
            ->whereIn('clearance_steps.office_designation_id', $designationIds)
            ->whereIn('clearance_steps.status', [
                ClearanceStep::STATUS_APPROVED,
                ClearanceStep::STATUS_FLAGGED,
            ]);
    }

    private function applyArchiveSort($query, string $sort)
    {
        return match ($sort) {
            'processed_asc' => $query
                ->orderBy('clearance_steps.signed_at')
                ->orderBy('clearance_steps.updated_at'),
            'student_asc' => $query
                ->orderBy('users.last_name')
                ->orderBy('users.first_name')
                ->orderBy('users.name')
                ->orderByDesc('clearance_steps.signed_at'),
            'student_id_asc' => $query
                ->orderBy('students.student_id_number')
                ->orderByDesc('clearance_steps.signed_at'),
            'program_asc' => $query
                ->orderBy('programs.code')
                ->orderBy('users.last_name')
                ->orderByDesc('clearance_steps.signed_at'),
            default => $query
                ->orderByDesc('clearance_steps.signed_at')
                ->orderByDesc('clearance_steps.updated_at'),
        };
    }
}
