<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClearanceRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClearanceRequestController extends Controller
{
    /**
     * GET /api/clearance/status
     *
     * Returns the student's active clearance request for the current
     * academic period plus its signatures, if any.
     */
    public function status(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->is_student) {
            return response()->json([
                'error' => 'Only students can view clearance status',
            ], 403);
        }

        $semester = config('clearance.current_semester');
        $academicYear = config('clearance.current_academic_year');

        try {
            $active = $user->clearanceRequests()
                ->where('semester', $semester)
                ->where('academic_year', $academicYear)
                ->where('status', 'pending')
                ->with(['signatures.designation'])
                ->latest('created_at')
                ->first();

            if (!$active) {
                return response()->json([
                    'clearance_request' => null,
                    'clearance_signatures' => [],
                ]);
            }

            $signatures = $active->signatures->map(function ($signature) {
                return [
                    'id' => $signature->id,
                    'clearance_request_id' => $signature->clearance_request_id,
                    'designation_id' => $signature->designation_id,
                    'status' => $signature->status,
                    'rejection_reason' => $signature->rejection_reason,
                    'remarks' => $signature->remarks,
                    'signed_by_user_id' => $signature->signed_by_user_id,
                    'approved_at' => $signature->approved_at,
                    'created_at' => $signature->created_at,
                    'updated_at' => $signature->updated_at,
                    'designation' => $signature->designation,
                ];
            });

            return response()->json([
                'clearance_request' => $active,
                'clearance_signatures' => $signatures,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch clearance status', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while fetching clearance status.',
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->is_student) {
            return response()->json([
                'error' => 'Only students can create clearance requests',
            ], 403);
        }

        $semester = config('clearance.current_semester');
        $academicYear = config('clearance.current_academic_year');

        try {
            $existing = $user->clearanceRequests()
                ->where('semester', $semester)
                ->where('academic_year', $academicYear)
                ->where('status', 'pending')
                ->first();

            if ($existing) {
                return response()->json([
                    'error' => 'You already have a pending clearance request for this academic period',
                ], 400);
            }

            $clearanceRequest = DB::transaction(function () use ($user, $semester, $academicYear) {
                return $user->clearanceRequests()->create([
                    'semester' => $semester,
                    'academic_year' => $academicYear,
                    'status' => 'pending',
                ]);
            });

            return response()->json($clearanceRequest, 201);
        } catch (\Throwable $e) {
            Log::error('Failed to create clearance request', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'An unexpected error occurred while creating the clearance request.',
            ], 500);
        }
    }
}
