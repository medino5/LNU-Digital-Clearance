<?php

namespace App\Http\Controllers;

use App\Models\ClearanceSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffDashboardController extends Controller
{
    /**
     * Display the staff dashboard with pending clearance requests
     * for the logged-in staff member's designation.
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user || !$user->is_staff) {
            abort(403, 'Unauthorized.');
        }

        // Assume each staff is linked to at least one designation.
        // Use the first designation for now.
        $designation = $user->designations()->first();

        if (!$designation) {
            $pendingSignatures = collect();

            return view('staff.dashboard', [
                'pendingSignatures' => $pendingSignatures,
                'designation' => null,
                'staff' => $user,
            ]);
        }

        $pendingSignatures = ClearanceSignature::with([
            'clearanceRequest.student.program',
            'designation',
        ])
            ->where('designation_id', $designation->id)
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        return view('staff.dashboard', [
            'pendingSignatures' => $pendingSignatures,
            'designation' => $designation,
            'staff' => $user,
        ]);
    }

    /**
     * Process an individual clearance signature (approve/reject).
     */
    public function processSignature(Request $request, ClearanceSignature $signature)
    {
        $user = Auth::user();

        if (!$user || !$user->is_staff) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $designation = $user->designations()->first();

        if (!$designation || $signature->designation_id !== $designation->id) {
            abort(403, 'Unauthorized.');
        }

        $status = $validated['status'];

        $signature->update([
            'status' => $status,
            'signed_by_user_id' => $user->id,
            'approved_at' => $status === 'approved' ? now() : null,
        ]);

        $clearance = $signature->clearanceRequest;

        if ($clearance) {
            $remainingPending = ClearanceSignature::where('clearance_request_id', $clearance->id)
                ->where('status', 'pending')
                ->count();

            if ($remainingPending === 0) {
                $clearance->update(['status' => 'completed']);
            }
        }

        return back()->with('success', 'Student clearance updated successfully!');
    }
}

