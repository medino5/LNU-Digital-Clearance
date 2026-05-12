<?php

namespace App\Http\Controllers;

use App\Models\ClearanceStep;
use App\Services\ClearanceWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

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
        $archiveLoadError = null;

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
                try {
                    $archiveNeedsJoins = $archiveSearch !== ''
                        || in_array($archiveSort, ['student_asc', 'student_id_asc', 'program_asc'], true);
                    $archiveQuery = $this->applyArchiveFilters(
                        $this->archiveQuery($officeDesignations->pluck('id'), $archiveNeedsJoins),
                        $archiveSearch,
                        $archiveStatus,
                    );

                    $archiveSteps = $this->applyArchiveSort($archiveQuery, $archiveSort)
                        ->simplePaginate(20, ['clearance_steps.id'], 'archive_page')
                        ->withQueryString();

                    $archiveSteps = $this->hydrateArchiveRows($archiveSteps);
                } catch (Throwable $exception) {
                    report($exception);

                    $archiveSteps = collect();
                    $archiveLoadError = 'Archive history could not be loaded right now. Try applying a narrower search or reload the page.';
                }
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
            'archiveLoadError' => $archiveLoadError,
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

    private function archiveQuery($designationIds, bool $withStudentJoins = false)
    {
        $query = ClearanceStep::query()
            ->whereIn('clearance_steps.office_designation_id', $designationIds)
            ->whereIn('clearance_steps.status', [
                ClearanceStep::STATUS_APPROVED,
                ClearanceStep::STATUS_FLAGGED,
            ]);

        if (! $withStudentJoins) {
            return $query;
        }

        return $query
            ->join('clearances', 'clearance_steps.clearance_id', '=', 'clearances.id')
            ->leftJoin('students', 'clearances.student_id', '=', 'students.id')
            ->leftJoin('users', 'students.user_id', '=', 'users.id')
            ->leftJoin('programs', 'students.program_id', '=', 'programs.id');
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

    private function hydrateArchiveRows($archiveSteps)
    {
        $archiveStepIds = $archiveSteps->getCollection()
            ->pluck('id')
            ->filter()
            ->values();

        if ($archiveStepIds->isEmpty()) {
            $archiveSteps->setCollection(collect());

            return $archiveSteps;
        }

        $archiveRecords = ClearanceStep::query()
            ->with([
                'clearance.student.user',
                'clearance.student.program',
                'officeDesignation.program',
            ])
            ->whereIn('id', $archiveStepIds)
            ->get()
            ->keyBy('id');

        $archiveSteps->setCollection(
            $archiveStepIds
                ->map(fn ($id) => $archiveRecords->get($id))
                ->filter()
                ->map(fn (ClearanceStep $step) => $this->archiveStepRow($step))
                ->values(),
        );

        return $archiveSteps;
    }

    private function archiveStepRow(ClearanceStep $step): array
    {
        $clearance = $step->clearance;
        $student = $clearance?->student;
        $studentName = $student?->displayName() ?: 'Student record unavailable';
        $studentId = $student?->student_id_number ?: 'No ID';
        $programCode = $student?->program?->code ?: 'No program';
        $yearLevel = $student?->yearLevelLabel() ?: 'No year level';
        $studentPhoto = $student?->user?->profilePhotoUrl();
        $isRejected = $step->status === ClearanceStep::STATUS_FLAGGED;

        return [
            'id' => $step->id,
            'status' => $step->status,
            'status_label' => $isRejected ? 'Rejected' : 'Approved',
            'student_name' => $studentName,
            'student_initial' => $this->nameInitial($studentName),
            'student_meta' => $studentId . ' | ' . $programCode . ' | ' . $yearLevel,
            'student_photo' => $studentPhoto,
            'student_profile_url' => $student ? route('office.students.show', $student) : '#',
            'student_profile_disabled' => ! $student,
            'clearance_status' => $clearance?->status
                ? ucwords(str_replace('_', ' ', $clearance->status))
                : 'Unavailable',
            'last_processed' => $step->signed_at?->format('M d, Y h:i A') ?? '-',
            'processed_label' => $step->signed_at?->format('M d, Y h:i A') ?? 'Pending timestamp',
            'office_label' => $step->office_label ?: '-',
            'note_label' => $isRejected ? 'Reject Reason' : 'Processed Note',
            'meta_note_label' => $isRejected ? 'Reject Reason' : 'Remarks',
            'remarks' => $step->remarks ?: '-',
            'process_url' => route('office.steps.process', $step),
        ];
    }

    private function nameInitial(string $name): string
    {
        $firstCharacter = function_exists('mb_substr')
            ? mb_substr($name, 0, 1)
            : substr($name, 0, 1);

        return strtoupper($firstCharacter ?: '?');
    }
}
