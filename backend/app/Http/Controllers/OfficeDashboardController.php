<?php

namespace App\Http\Controllers;

use App\Models\ClearanceStep;
use App\Models\OfficeDesignation;
use App\Models\Program;
use App\Services\ClearanceWorkflowService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
            'pending_search' => ['nullable', 'string', 'max:120'],
            'pending_program' => ['nullable', 'integer', 'exists:programs,id'],
            'pending_year' => ['nullable', 'integer', 'between:1,4'],
            'pending_section' => ['nullable', 'regex:/^[1-4]-[1-6]$/'],
            'pending_sort' => ['nullable', 'in:waiting_desc,waiting_asc,student_asc,student_id_asc,program_asc,year_asc,section_asc'],
            'archive_search' => ['nullable', 'string', 'max:120'],
            'archive_status' => ['nullable', 'in:' . ClearanceStep::STATUS_APPROVED . ',' . ClearanceStep::STATUS_FLAGGED],
            'archive_sort' => ['nullable', 'in:processed_desc,processed_asc,student_asc,student_id_asc,program_asc'],
        ], [
            'pending_search.max' => 'Queue search must be 120 characters or fewer.',
            'pending_section.regex' => 'Section must use the year-section format, for example 3-2.',
            'archive_search.max' => 'Archive search must be 120 characters or fewer.',
        ]);

        $tab = $request->query('tab') === 'archive' ? 'archive' : 'active';
        $pendingSearch = trim((string) $request->query('pending_search', ''));
        $pendingProgram = $request->filled('pending_program')
            ? (int) $request->query('pending_program')
            : null;
        $pendingYear = $request->filled('pending_year')
            ? (int) $request->query('pending_year')
            : null;
        $pendingSection = $request->filled('pending_section')
            ? trim((string) $request->query('pending_section'))
            : null;
        $pendingSort = in_array($request->query('pending_sort'), [
            'waiting_desc',
            'waiting_asc',
            'student_asc',
            'student_id_asc',
            'program_asc',
            'year_asc',
            'section_asc',
        ], true) ? $request->query('pending_sort') : 'waiting_desc';
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
        $queueScope = $this->queueScope($officeDesignations);

        $pendingSteps = collect();
        $archiveSteps = collect();
        $pendingCount = 0;
        $archiveCount = 'History';
        $archiveLoadError = null;
        $workloadReport = [
            'total' => 0,
            'by_program' => collect(),
            'by_year' => collect(),
            'by_section' => collect(),
        ];
        $programOptions = Program::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $yearLevelOptions = [
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
        ];

        if (! $queueScope['show_program_filter']) {
            $pendingProgram = null;
        }

        if (! $queueScope['show_year_filter']) {
            $pendingYear = null;
        }

        $sectionOptions = $this->sectionOptions($pendingYear ?: $queueScope['fixed_year']);

        if ($hasActiveDesignation) {
            $designationIds = $officeDesignations->pluck('id');
            $baseQuery = $this->pendingQuery($designationIds);

            $pendingCount = (clone $baseQuery)
                ->count();
            $workloadReport = $this->workloadReport((clone $baseQuery));

            if ($tab === 'active') {
                $pendingSteps = $this->applyPendingSort(
                    $this->applyPendingFilters(
                        (clone $baseQuery),
                        $pendingSearch,
                        $pendingProgram,
                        $pendingYear,
                        $pendingSection,
                    ),
                    $pendingSort,
                )
                    ->paginate(10, ['clearance_steps.*'], 'pending_page')
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
                        ->paginate(10, ['clearance_steps.id'], 'archive_page')
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
            'pendingSearch' => $pendingSearch,
            'pendingProgram' => $pendingProgram,
            'pendingYear' => $pendingYear,
            'pendingSection' => $pendingSection,
            'pendingSort' => $pendingSort,
            'queueScope' => $queueScope,
            'workloadReport' => $workloadReport,
            'archiveCount' => $archiveCount,
            'archiveSearch' => $archiveSearch,
            'archiveStatus' => $archiveStatus,
            'archiveSort' => $archiveSort,
            'archiveLoadError' => $archiveLoadError,
            'programOptions' => $programOptions,
            'yearLevelOptions' => $yearLevelOptions,
            'sectionOptions' => $sectionOptions,
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

    private function pendingQuery($designationIds)
    {
        return $this->applyVpsdReadinessGate(
            ClearanceStep::query()
                ->select('clearance_steps.*')
                ->join('clearances', 'clearance_steps.clearance_id', '=', 'clearances.id')
                ->leftJoin('students', 'clearances.student_id', '=', 'students.id')
                ->leftJoin('users', 'students.user_id', '=', 'users.id')
                ->leftJoin('programs', 'students.program_id', '=', 'programs.id')
                ->leftJoin('office_designations', 'clearance_steps.office_designation_id', '=', 'office_designations.id')
                ->with([
                    'clearance.student.user',
                    'clearance.student.program',
                    'clearance.steps:id,clearance_id,status,office_type',
                    'officeDesignation.program',
                ])
                ->whereIn('clearance_steps.office_designation_id', $designationIds)
                ->where('clearance_steps.status', ClearanceStep::STATUS_AWAITING_ACTION)
        );
    }

    private function applyVpsdReadinessGate($query)
    {
        return $query->where(function ($query) {
            $query->where('office_designations.office_type', '!=', OfficeDesignation::TYPE_VPSD)
                ->orWhere(function ($vpsdQuery) {
                    $vpsdQuery
                        ->where('office_designations.office_type', OfficeDesignation::TYPE_VPSD)
                        ->whereNotExists(function ($subQuery) {
                            $subQuery
                                ->selectRaw('1')
                                ->from('clearance_steps as prerequisite_steps')
                                ->whereColumn('prerequisite_steps.clearance_id', 'clearance_steps.clearance_id')
                                ->whereColumn('prerequisite_steps.id', '!=', 'clearance_steps.id')
                                ->where('prerequisite_steps.status', '!=', ClearanceStep::STATUS_APPROVED);
                        });
                });
        });
    }

    private function applyPendingFilters($query, string $search, ?int $programId, ?int $yearLevel, ?string $section)
    {
        if ($programId) {
            $query->where('students.program_id', $programId);
        }

        if ($yearLevel) {
            $query->where('students.year_level', $yearLevel);
        }

        if ($section !== null && $section !== '') {
            $query->where('students.section', $section);
        }

        if ($search !== '') {
            $searchLike = '%' . $search . '%';

            $query->where(function ($query) use ($searchLike) {
                $query->where('clearance_steps.office_label', 'like', $searchLike)
                    ->orWhere('clearances.reference_number', 'like', $searchLike)
                    ->orWhere('students.student_id_number', 'like', $searchLike)
                    ->orWhere('students.section', 'like', $searchLike)
                    ->orWhere('programs.code', 'like', $searchLike)
                    ->orWhere('programs.name', 'like', $searchLike)
                    ->orWhere('users.name', 'like', $searchLike)
                    ->orWhere('users.first_name', 'like', $searchLike)
                    ->orWhere('users.last_name', 'like', $searchLike);
            });
        }

        return $query;
    }

    private function applyPendingSort($query, string $sort)
    {
        return match ($sort) {
            'waiting_asc' => $query
                ->orderBy('clearance_steps.created_at')
                ->orderBy('clearance_steps.id'),
            'student_asc' => $query
                ->orderBy('users.last_name')
                ->orderBy('users.first_name')
                ->orderBy('users.name')
                ->orderBy('clearance_steps.created_at'),
            'student_id_asc' => $query
                ->orderBy('students.student_id_number')
                ->orderBy('clearance_steps.created_at'),
            'program_asc' => $query
                ->orderBy('programs.code')
                ->orderBy('users.last_name')
                ->orderBy('clearance_steps.created_at'),
            'year_asc' => $query
                ->orderBy('students.year_level')
                ->orderBy('programs.code')
                ->orderBy('users.last_name'),
            'section_asc' => $query
                ->orderBy('students.section')
                ->orderBy('students.year_level')
                ->orderBy('programs.code')
                ->orderBy('users.last_name'),
            default => $query
                ->orderByDesc('clearance_steps.created_at')
                ->orderByDesc('clearance_steps.id'),
        };
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
                    ->orWhere('students.section', 'like', $searchLike)
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

    /**
     * @param  Collection<int, OfficeDesignation>  $officeDesignations
     * @return array{
     *     show_program_filter: bool,
     *     show_year_filter: bool,
     *     fixed_program_id: ?int,
     *     fixed_program_code: ?string,
     *     fixed_year: ?int,
     *     fixed_year_label: ?string
     * }
     */
    private function queueScope(Collection $officeDesignations): array
    {
        $designationCount = $officeDesignations->count();
        $programIds = $officeDesignations
            ->pluck('program_id')
            ->filter()
            ->unique()
            ->values();
        $yearLevels = $officeDesignations
            ->pluck('year_level')
            ->filter()
            ->unique()
            ->values();

        $hasSingleProgramScope = $designationCount > 0
            && $programIds->count() === 1
            && $officeDesignations->every(fn (OfficeDesignation $designation) => filled($designation->program_id));
        $hasSingleYearScope = $designationCount > 0
            && $yearLevels->count() === 1
            && $officeDesignations->every(fn (OfficeDesignation $designation) => filled($designation->year_level));

        $fixedYear = $hasSingleYearScope ? (int) $yearLevels->first() : null;
        $fixedProgramId = $hasSingleProgramScope ? (int) $programIds->first() : null;
        $fixedProgram = $fixedProgramId
            ? $officeDesignations->firstWhere('program_id', $fixedProgramId)?->program
            : null;

        return [
            'show_program_filter' => ! $hasSingleProgramScope,
            'show_year_filter' => ! $hasSingleYearScope,
            'fixed_program_id' => $fixedProgramId,
            'fixed_program_code' => $fixedProgram?->code,
            'fixed_year' => $fixedYear,
            'fixed_year_label' => $fixedYear ? ($this->yearLevelOptions()[$fixedYear] ?? 'Year ' . $fixedYear) : null,
        ];
    }

    /**
     * @return array{total: int, by_program: Collection<int, object>, by_year: Collection<int, object>, by_section: Collection<int, object>}
     */
    private function workloadReport($baseQuery): array
    {
        return [
            'total' => (clone $baseQuery)->count(),
            'by_program' => $this->workloadAggregateQuery($baseQuery)
                ->selectRaw("COALESCE(programs.code, 'No program') as label, COUNT(*) as total")
                ->groupBy('programs.code')
                ->orderBy('programs.code')
                ->get(),
            'by_year' => $this->workloadAggregateQuery($baseQuery)
                ->selectRaw('students.year_level as year_level, COUNT(*) as total')
                ->groupBy('students.year_level')
                ->orderBy('students.year_level')
                ->get()
                ->map(function ($row) {
                    $year = (int) $row->year_level;
                    $row->label = $this->yearLevelOptions()[$year] ?? 'No year level';

                    return $row;
                }),
            'by_section' => $this->workloadAggregateQuery($baseQuery)
                ->selectRaw("COALESCE(students.section, 'No section') as label, students.year_level as year_level, COUNT(*) as total")
                ->groupBy('students.section', 'students.year_level')
                ->orderBy('students.year_level')
                ->orderBy('students.section')
                ->get(),
        ];
    }

    private function workloadAggregateQuery($baseQuery)
    {
        $query = (clone $baseQuery)
            ->withoutEagerLoads()
            ->reorder();

        // The pending queue query selects clearance_steps.* for card rendering.
        // Reset it before grouping so MySQL production does not reject the
        // aggregate with ONLY_FULL_GROUP_BY enabled.
        $query->getQuery()->columns = null;

        return $query;
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
        $section = $student?->sectionLabel() ?: 'No section';
        $studentPhoto = $student?->user?->profilePhotoUrl();
        $isRejected = $step->status === ClearanceStep::STATUS_FLAGGED;

        return [
            'id' => $step->id,
            'status' => $step->status,
            'status_label' => $isRejected ? 'Rejected' : 'Approved',
            'student_name' => $studentName,
            'student_initial' => $this->nameInitial($studentName),
            'student_meta' => $studentId . ' | ' . $programCode . ' | ' . $yearLevel . ' | ' . $section,
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
            'meta_note_label' => $isRejected ? 'Reject Reason' : 'Processed Note',
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

    /**
     * @return array<int, string>
     */
    private function yearLevelOptions(): array
    {
        return [
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function sectionOptions(?int $onlyYearLevel = null): array
    {
        $sections = [];

        $yearLevels = $onlyYearLevel ? [$onlyYearLevel] : [1, 2, 3, 4];

        foreach ($yearLevels as $yearLevel) {
            foreach ([1, 2, 3, 4, 5, 6] as $sectionNumber) {
                $sections[] = $yearLevel . '-' . $sectionNumber;
            }
        }

        return $sections;
    }
}
