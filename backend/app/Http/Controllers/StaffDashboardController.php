<?php

namespace App\Http\Controllers;

use App\Models\ClearanceSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'clearanceRequest.signatures.designation',
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

        $normalizedAction = $request->input('action');

        if (!$normalizedAction && $request->filled('status')) {
            $normalizedAction = match ($request->input('status')) {
                'approved' => 'approve',
                'rejected' => 'reject',
                default => $request->input('status'),
            };
        }

        $request->merge([
            'action' => $normalizedAction,
        ]);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|string',
            'remarks' => 'nullable|string',
        ]);

        $designation = $user->designations()->first();

        if (!$designation || $signature->designation_id !== $designation->id) {
            abort(403, 'Unauthorized.');
        }

        try {
            $signature->loadMissing('designation');
            $updatedSignature = [];
            $actionMessage = null;

            DB::transaction(function () use ($validated, $user, $signature, $designation, &$updatedSignature, &$actionMessage) {
                $action = $validated['action'];
                $status = $action === 'approve' ? 'approved' : 'rejected';
                $rejectionReason = $action === 'reject'
                    ? trim($validated['rejection_reason'])
                    : null;

                $signature->update([
                    'status' => $status,
                    'signed_by_user_id' => $user->id,
                    'approved_at' => $status === 'approved' ? now() : null,
                    'remarks' => $validated['remarks'] ?? null,
                    'rejection_reason' => $rejectionReason,
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

                $signature->refresh();
                $updatedSignature = [
                    'id' => $signature->id,
                    'status' => $signature->status,
                    'rejection_reason' => $signature->rejection_reason,
                    'remarks' => $signature->remarks,
                    'signed_by_user_id' => $signature->signed_by_user_id,
                    'approved_at' => $signature->approved_at,
                    'updated_at' => $signature->updated_at,
                ];

                $officeName = $signature->designation?->name ?? $designation->name ?? 'Office';
                $actionMessage = $action === 'approve'
                    ? $officeName . ' - Approved'
                    : $officeName . ' - Rejected: ' . $rejectionReason;
            });

            return back()
                ->with('success', $actionMessage ?? 'Student clearance updated successfully!')
                ->with('updated_signature', $updatedSignature);
        } catch (\Throwable $e) {
            Log::error('Failed to process clearance signature', [
                'signature_id' => $signature->id,
                'staff_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'An unexpected error occurred while updating the clearance.');
        }
    }
}
