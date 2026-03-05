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
}

