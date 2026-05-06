<?php

namespace App\Http\Controllers;

use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        $staffUsers = $this->staffCandidatePool();
        [$studentsByProgram, $studentsByYear] = $this->studentCandidatePools($designations);

        $designations = $designations->map(function (OfficeDesignation $designation) use ($staffUsers, $studentsByProgram, $studentsByYear) {
            $eligibleUsers = $this->eligibleUsersForDesignation(
                $designation,
                $staffUsers,
                $studentsByProgram,
                $studentsByYear,
            );

            $currentAssignment = $designation->activeAssignments->first();

            $designation->setRelation('eligible_users', $eligibleUsers);
            $designation->setRelation('current_assignment', $currentAssignment);

            return $designation;
        });

        return view('admin.routing', [
            'designations' => $designations,
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    private function staffCandidatePool(): Collection
    {
        return $this->sortUsers(
            User::with(['officeAccount.program', 'studentProfile.program'])
                ->where('role', '!=', User::ROLE_ADMIN)
                ->whereHas('officeAccount')
                ->get()
        );
    }

    /**
     * @param  Collection<int, OfficeDesignation>  $designations
     * @return array{0: Collection<string, Collection<int, User>>, 1: Collection<string, Collection<int, User>>}
     */
    private function studentCandidatePools(Collection $designations): array
    {
        $programIds = $designations
            ->where('office_type', OfficeDesignation::TYPE_ACAD_ORG_TREASURER)
            ->pluck('program_id')
            ->filter()
            ->unique()
            ->values();

        $yearLevels = $designations
            ->where('office_type', OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER)
            ->pluck('year_level')
            ->filter()
            ->unique()
            ->values();

        if ($programIds->isEmpty() && $yearLevels->isEmpty()) {
            return [collect(), collect()];
        }

        $studentUsers = User::with(['studentProfile.program'])
            ->where('role', User::ROLE_STUDENT)
            ->whereHas('studentProfile', function ($query) use ($programIds, $yearLevels) {
                $query->where(function ($studentQuery) use ($programIds, $yearLevels) {
                    if ($programIds->isNotEmpty()) {
                        $studentQuery->whereIn('program_id', $programIds);
                    }

                    if ($yearLevels->isNotEmpty()) {
                        $method = $programIds->isNotEmpty() ? 'orWhereIn' : 'whereIn';
                        $studentQuery->{$method}('year_level', $yearLevels);
                    }
                });
            })
            ->get();

        $studentsByProgram = $studentUsers
            ->filter(fn (User $user) => $user->studentProfile?->program_id)
            ->groupBy(fn (User $user) => (string) $user->studentProfile->program_id)
            ->map(fn (Collection $users) => $this->sortUsers($users));

        $studentsByYear = $studentUsers
            ->filter(fn (User $user) => $user->studentProfile?->year_level)
            ->groupBy(fn (User $user) => (string) $user->studentProfile->year_level)
            ->map(fn (Collection $users) => $this->sortUsers($users));

        return [$studentsByProgram, $studentsByYear];
    }

    /**
     * @param  Collection<int, User>  $staffUsers
     * @param  Collection<string, Collection<int, User>>  $studentsByProgram
     * @param  Collection<string, Collection<int, User>>  $studentsByYear
     * @return Collection<int, User>
     */
    private function eligibleUsersForDesignation(
        OfficeDesignation $designation,
        Collection $staffUsers,
        Collection $studentsByProgram,
        Collection $studentsByYear,
    ): Collection {
        if (! $designation->isStudentLed()) {
            return $staffUsers;
        }

        return match ($designation->office_type) {
            OfficeDesignation::TYPE_ACAD_ORG_TREASURER => $studentsByProgram->get((string) $designation->program_id, collect()),
            OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER => $studentsByYear->get((string) $designation->year_level, collect()),
            default => collect(),
        };
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
