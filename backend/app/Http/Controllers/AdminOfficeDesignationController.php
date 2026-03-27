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
    public function updateAssignment(Request $request, OfficeDesignation $officeDesignation): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $user = User::with('officeAccount')->findOrFail($validated['user_id']);

        if (! $user->isOffice() || ! $user->officeAccount) {
            return back()->with('error', 'The selected user is not a valid office account.');
        }

        if (! $this->isEligibleForDesignation($user, $officeDesignation)) {
            return back()->with('error', 'The selected office user is not eligible for this designation.');
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

        return back()->with('success', 'Designation assignment updated successfully.');
    }

    protected function isEligibleForDesignation(User $user, OfficeDesignation $officeDesignation): bool
    {
        $officeAccount = $user->officeAccount;

        if (! $officeAccount) {
            return false;
        }

        if ($officeAccount->office_type !== $officeDesignation->office_type) {
            return false;
        }

        if ((int) $officeDesignation->program_id !== (int) $officeAccount->program_id) {
            if ($officeDesignation->program_id || $officeAccount->program_id) {
                return false;
            }
        }

        if ((int) $officeDesignation->year_level !== (int) $officeAccount->year_level) {
            if ($officeDesignation->year_level || $officeAccount->year_level) {
                return false;
            }
        }

        return true;
    }
}