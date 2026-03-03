<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClearanceRequest;

class ClearanceRequestController extends Controller
{
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

        $clearanceRequest = $user->clearanceRequests()->create([
            'semester' => $semester,
            'academic_year' => $academicYear,
            'status' => 'pending',
        ]);

        return response()->json($clearanceRequest, 201);
    }

    public function getStatus(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->is_student) {
            return response()->json([
                'error' => 'Only students can view clearance status',
            ], 403);
        }

        $clearance = ClearanceRequest::with(['signatures.designation'])
            ->where('student_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$clearance) {
            return response()->json([
                'data' => null,
                'message' => 'No active clearance request.',
            ], 200);
        }

        return response()->json([
            'data' => $clearance,
        ], 200);
    }
}

