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

        $designations = $designations->map(function (OfficeDesignation $designation) {
            $currentAssignment = $designation->activeAssignments->first();

            $designation->setRelation('current_assignment', $currentAssignment);

            return $designation;
        });

        return view('admin.routing', [
            'designations' => $designations,
        ]);
    }

    public function eligibleUsers(OfficeDesignation $officeDesignation)
    {
        $officeDesignation->loadMissing('program');

        $users = $this->candidateQueryForDesignation($officeDesignation)
            ->get()
            ->filter(fn (User $user) => $officeDesignation->matchesUser($user));

        $currentUserId = $officeDesignation->activeAssignments()->value('user_id');

        return response()->json([
            'designation' => [
                'id' => $officeDesignation->id,
                'name' => $officeDesignation->display_name,
                'scope' => $officeDesignation->scopeLabel() ?? 'Whole school',
            ],
            'current_user_id' => $currentUserId,
            'users' => $this->sortUsers($users)
                ->map(fn (User $user) => $this->formatEligibleUser($user))
                ->values(),
        ]);
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
