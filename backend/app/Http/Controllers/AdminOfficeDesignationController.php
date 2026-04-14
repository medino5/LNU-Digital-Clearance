<?php

namespace App\Http\Controllers;

use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOfficeDesignationController extends Controller
{
    public function index()
    {
        $designationQuery = OfficeDesignation::with([
            'program',
            'activeAssignments.user.officeAccount.program',
            'activeAssignments.user.studentProfile.program',
        ])
            ->where('is_active', true)
            ->orderBy('office_type')
            ->orderBy('display_name');

        $designationCandidates = User::with([
            'officeAccount.program',
            'studentProfile.program',
        ])
            ->where('role', '!=', User::ROLE_ADMIN)
            ->where(function ($query) {
                $query->whereHas('officeAccount')
                    ->orWhereHas('studentProfile');
            })
            ->get();

        $designations = $designationQuery->get()->map(function (OfficeDesignation $designation) use ($designationCandidates) {
            $eligibleUsers = $designationCandidates
                ->filter(fn (User $user) => $designation->matchesUser($user))
                ->sortBy(function (User $user) {
                    return strtolower($user->officeAccount->display_name ?? $user->formattedName());
                })
                ->values();

            $currentAssignment = $designation->activeAssignments->first();

            $designation->setRelation('eligible_users', $eligibleUsers);
            $designation->setRelation('current_assignment', $currentAssignment);

            return $designation;
        });

        return view('admin.routing', [
            'designations' => $designations,
        ]);
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
