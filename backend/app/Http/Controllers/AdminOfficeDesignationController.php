<?php

namespace App\Http\Controllers;

use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\Program;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminOfficeDesignationController extends Controller
{
    public function index()
    {
        $designations = OfficeDesignation::with([
            'program',
            'activeAssignments.user.officeAccount.program',
            'activeAssignments.user.studentProfile.program',
        ])
            ->where('is_active', true)
            ->orderBy('office_type')
            ->orderBy('display_name')
            ->get();

        $designations = $designations->map(function (OfficeDesignation $designation) {
            $currentAssignment = $designation->activeAssignments->first();

            $designation->setRelation('current_assignment', $currentAssignment);

            return $designation;
        });

        return view('admin.routing', [
            'designations' => $designations,
            'programs' => Program::query()->orderBy('code')->get(['id', 'code', 'name', 'org_name']),
            'designationTypeOptions' => OfficeDesignation::typeOptions(),
        ]);
    }

    public function eligibleUsers(Request $request, OfficeDesignation $officeDesignation)
    {
        $officeDesignation->loadMissing('program');
        $search = Str::lower(trim($request->string('search')->toString()));
        $currentUserId = $officeDesignation->activeAssignments()->value('user_id');

        $minimumStudentSearchLength = 2;

        if ($officeDesignation->isStudentLed() && strlen($search) < $minimumStudentSearchLength) {
            $users = $currentUserId
                ? User::query()
                    ->with(['studentProfile.program'])
                    ->whereKey($currentUserId)
                    ->get()
                : collect();

            return response()->json([
                'designation' => [
                    'id' => $officeDesignation->id,
                    'name' => $officeDesignation->display_name,
                    'scope' => $officeDesignation->scopeLabel() ?? 'Whole school',
                ],
                'current_user_id' => $currentUserId,
                'requires_search' => true,
                'minimum_search_length' => $minimumStudentSearchLength,
                'users' => $this->sortUsers($users)
                    ->map(fn (User $user) => $this->formatEligibleUser($user))
                    ->values(),
            ]);
        }

        $users = $this->candidateQueryForDesignation($officeDesignation)
            ->when($search !== '', fn ($query) => $this->applyEligibleUserSearch($query, $search, $officeDesignation))
            ->limit(30)
            ->get()
            ->filter(fn (User $user) => $officeDesignation->matchesUser($user));

        return response()->json([
            'designation' => [
                'id' => $officeDesignation->id,
                'name' => $officeDesignation->display_name,
                'scope' => $officeDesignation->scopeLabel() ?? 'Whole school',
            ],
            'current_user_id' => $currentUserId,
            'requires_search' => false,
            'minimum_search_length' => $officeDesignation->isStudentLed() ? $minimumStudentSearchLength : null,
            'users' => $this->sortUsers($users)
                ->map(fn (User $user) => $this->formatEligibleUser($user))
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $redirectTo = route('admin.routing.index') . '#designation-create';

        $validated = $this->validateForm(
            $request,
            'designationCreate',
            [
                'display_name' => ['nullable', 'string', 'max:120', 'regex:/^[\pL\pM0-9 .,&()\'\\-\/]+$/u'],
                'office_type' => ['required', Rule::in(array_keys(OfficeDesignation::typeOptions()))],
                'program_id' => ['nullable', 'integer', 'exists:programs,id'],
                'year_level' => ['nullable', 'integer', 'between:1,4'],
            ],
            $redirectTo,
            [
                'display_name.regex' => 'Use only letters, numbers, spaces, and common punctuation for the designation name.',
            ],
        );

        $officeType = $validated['office_type'];
        $programId = $validated['program_id'] ?? null;
        $yearLevel = $validated['year_level'] ?? null;

        if (in_array($officeType, [
            OfficeDesignation::TYPE_ACAD_ORG_TREASURER,
            OfficeDesignation::TYPE_ACAD_ORG_ADVISER,
        ], true) && ! $programId) {
            throw $this->formValidationException(
                ['program_id' => 'Choose a program for this designation.'],
                'designationCreate',
                $redirectTo,
            );
        }

        if ($officeType === OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER && ! $yearLevel) {
            throw $this->formValidationException(
                ['year_level' => 'Choose a year level for this designation.'],
                'designationCreate',
                $redirectTo,
            );
        }

        if (! in_array($officeType, [
            OfficeDesignation::TYPE_ACAD_ORG_TREASURER,
            OfficeDesignation::TYPE_ACAD_ORG_ADVISER,
        ], true)) {
            $programId = null;
        }

        if ($officeType !== OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER) {
            $yearLevel = null;
        }

        $program = $programId ? Program::query()->find($programId) : null;
        $displayName = trim($validated['display_name'] ?? '') ?: $this->defaultDesignationName($officeType, $program, $yearLevel);
        $key = $this->designationKey($officeType, $program, $yearLevel);

        OfficeDesignation::query()->updateOrCreate(
            ['key' => $key],
            [
                'display_name' => $displayName,
                'office_type' => $officeType,
                'program_id' => $program?->id,
                'year_level' => $yearLevel,
                'is_active' => true,
            ],
        );

        return $this->redirectWithMessage(
            route('admin.routing.index'),
            'success',
            'Routing office saved successfully.',
        );
    }

    public function destroy(OfficeDesignation $officeDesignation): RedirectResponse
    {
        DB::transaction(function () use ($officeDesignation) {
            $officeDesignation->activeAssignments()
                ->update([
                    'is_active' => false,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            $officeDesignation->update(['is_active' => false]);
        });

        return $this->redirectWithMessage(
            route('admin.routing.index'),
            'success',
            'Routing office removed from new clearances.',
        );
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, User>
     */
    private function sortUsers(Collection $users): Collection
    {
        return $users
            ->sortBy(fn (User $user) => strtolower($user->officeAccount->display_name ?? $user->formattedName()))
            ->values();
    }

    private function candidateQueryForDesignation(OfficeDesignation $designation)
    {
        if (! $designation->isStudentLed()) {
            return User::with(['officeAccount.program', 'studentProfile.program'])
                ->where('role', '!=', User::ROLE_ADMIN)
                ->whereHas('officeAccount');
        }

        return User::with(['studentProfile.program'])
            ->where('role', User::ROLE_STUDENT)
            ->whereHas('studentProfile', function ($query) use ($designation) {
                if ($designation->office_type === OfficeDesignation::TYPE_ACAD_ORG_TREASURER) {
                    $query->where('program_id', $designation->program_id);

                    return;
                }

                if ($designation->office_type === OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER) {
                    $query->where('year_level', $designation->year_level);
                }
            });
    }

    private function applyEligibleUserSearch($query, string $search, OfficeDesignation $designation): void
    {
        $query->where(function ($searchQuery) use ($search, $designation) {
            $searchQuery
                ->whereRaw('LOWER(users.name) LIKE ?', ['%' . $search . '%'])
                ->orWhereRaw('LOWER(users.first_name) LIKE ?', ['%' . $search . '%'])
                ->orWhereRaw('LOWER(users.last_name) LIKE ?', ['%' . $search . '%'])
                ->orWhereRaw('LOWER(users.username) LIKE ?', ['%' . $search . '%']);

            if ($designation->isStudentLed()) {
                $searchQuery->orWhereHas('studentProfile', function ($studentQuery) use ($search) {
                    $studentQuery->whereRaw('LOWER(student_id_number) LIKE ?', ['%' . $search . '%']);
                });

                return;
            }

            $searchQuery->orWhereHas('officeAccount', function ($officeQuery) use ($search) {
                $officeQuery->whereRaw('LOWER(display_name) LIKE ?', ['%' . $search . '%']);
            });
        });
    }

    /**
     * @return array{id:int,label:string,meta:string,type:string}
     */
    private function formatEligibleUser(User $user): array
    {
        $officeAccount = $user->officeAccount;
        $studentProfile = $user->studentProfile;

        $type = $officeAccount ? 'Staff' : ($studentProfile ? 'Student' : 'User');
        $name = $officeAccount?->display_name ?? $user->formattedName();
        $meta = collect([
            $officeAccount?->officeTypeLabel(),
            $officeAccount?->scopeSummaryLabel(),
            $studentProfile?->program?->code,
            $studentProfile?->year_level ? 'Year ' . $studentProfile->year_level : null,
        ])->filter()->implode(' / ');

        return [
            'id' => $user->id,
            'label' => $type . ' - ' . $name,
            'meta' => $meta,
            'type' => $type,
        ];
    }

    private function designationKey(string $officeType, ?Program $program, ?int $yearLevel): string
    {
        return match ($officeType) {
            OfficeDesignation::TYPE_ACAD_ORG_TREASURER => Str::lower(($program?->code ?? 'program') . '-acad-org-treasurer'),
            OfficeDesignation::TYPE_ACAD_ORG_ADVISER => Str::lower(($program?->code ?? 'program') . '-acad-org-adviser'),
            OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER => 'year-' . max(1, (int) $yearLevel) . '-treasurer',
            OfficeDesignation::TYPE_LIBRARIAN => 'college-librarian',
            OfficeDesignation::TYPE_VPSD => 'vpsd-office',
            default => Str::slug($officeType),
        };
    }

    private function defaultDesignationName(string $officeType, ?Program $program, ?int $yearLevel): string
    {
        return match ($officeType) {
            OfficeDesignation::TYPE_ACAD_ORG_TREASURER => sprintf(
                '%s Academic Organization Treasurer',
                $program?->org_name ?? $program?->code ?? 'Program',
            ),
            OfficeDesignation::TYPE_ACAD_ORG_ADVISER => sprintf(
                '%s Academic Organization Adviser',
                $program?->org_name ?? $program?->code ?? 'Program',
            ),
            OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER => sprintf(
                '%s Level Organization Treasurer',
                match ((int) $yearLevel) {
                    1 => '1st Year',
                    2 => '2nd Year',
                    3 => '3rd Year',
                    4 => '4th Year',
                    default => 'Year Level',
                },
            ),
            default => OfficeDesignation::typeOptions()[$officeType] ?? Str::headline($officeType),
        };
    }

    public function updateAssignment(Request $request, OfficeDesignation $officeDesignation): RedirectResponse
    {
        $redirectTo = route('admin.routing.index');

        $validated = $this->validateForm(
            $request,
            'designationAssignment',
            [
                'user_id' => ['required', 'integer', 'exists:users,id'],
            ],
            $redirectTo,
        );

        $user = User::with([
            'officeAccount.program',
            'studentProfile.program',
        ])->findOrFail($validated['user_id']);

        if (! $officeDesignation->matchesUser($user)) {
            return $this->redirectWithInputAndMessage(
                $request,
                $redirectTo,
                'error',
                'The selected user is not eligible for this designation.',
            );
        }

        $activeAssignment = $officeDesignation->activeAssignments()->first();

        if ($activeAssignment && $activeAssignment->user_id === $user->id) {
            return $this->redirectWithInputAndMessage(
                $request,
                $redirectTo,
                'info',
                'Designation assignment is already up to date.',
            );
        }

        DB::transaction(function () use ($officeDesignation, $user) {
            OfficeDesignationAssignment::query()
                ->where('office_designation_id', $officeDesignation->id)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'released_at' => now(),
                    'updated_at' => now(),
                ]);

            OfficeDesignationAssignment::create([
                'office_designation_id' => $officeDesignation->id,
                'user_id' => $user->id,
                'assigned_by_user_id' => auth()->id(),
                'assigned_at' => now(),
                'released_at' => null,
                'is_active' => true,
            ]);
        });

        return $this->redirectWithMessage(
            $redirectTo,
            'success',
            'Designation assignment updated successfully.',
        );
    }
}
