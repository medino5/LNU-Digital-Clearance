<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClearanceRequest;
use Illuminate\Support\Facades\Auth;
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

    /**
     * DELETE /api/clearance
     *
     * Cancels the student's active clearance request (if any).
     */
    public function cancel(Request $request)
    {
        $user = Auth::user();

        try {
            return DB::transaction(function () use ($user) {
                $active = ClearanceRequest::where('student_id', $user->id)
                    ->where('status', 'pending')
                    ->first();

                if (!$active) {
                    return response()->json([
                        'message' => 'No active clearance request found.',
                    ], 404);
                }

                if ($active->student_id !== $user->id) {
                    return response()->json([
                        'message' => 'Unauthorized.',
                    ], 403);
                }

                if ($active->status === 'completed') {
                    return response()->json([
                        'message' => 'A completed clearance request cannot be cancelled.',
                    ], 422);
                }

                $active->signatures()->update([
                    'status' => 'cancelled',
                ]);

                $active->update([
                    'status' => 'cancelled',
                ]);

                return response()->json([
                    'message' => 'Clearance request cancelled successfully.',
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to cancel clearance request', [
                'user_id' => $user?->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to cancel clearance request.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/clearance/history
     *
     * Returns paginated clearance requests for the authenticated student.
     */
    public function history(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->is_student) {
            return response()->json([
                'message' => 'Only students can view clearance history.',
            ], 403);
        }

        try {
            $paginated = ClearanceRequest::where('student_id', $user->id)
                ->with('signatures')
                ->orderByDesc('created_at')
                ->paginate(10);

            $data = $paginated->getCollection()->map(function ($clearance) {
                $signatures = $clearance->signatures;

                return [
                    'id' => $clearance->id,
                    'semester' => $clearance->semester,
                    'status' => $clearance->status,
                    'created_at' => $clearance->created_at?->toISOString(),
                    'completed_at' => $clearance->status === 'completed'
                        ? $clearance->updated_at?->toISOString()
                        : null,
                    'total_signatures' => $signatures->count(),
                    'approved_signatures' => $signatures->where('status', 'approved')->count(),
                    'rejected_signatures' => $signatures->where('status', 'rejected')->count(),
                    'pending_signatures' => $signatures->where('status', 'pending')->count(),
                ];
            })->values();

            return response()->json([
                'data' => $data,
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch clearance history', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to fetch clearance history.',
                'error' => $e->getMessage(),
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
