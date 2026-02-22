<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organization;
use App\Models\Designation;
use App\Models\ClearanceRequest;
use App\Models\ClearanceSignature;
use Illuminate\Http\Request;

class ClearanceController extends Controller
{
    // ===============================
    // STUDENT CREATES CLEARANCE
    // ===============================
    public function create(Request $request)
    {
        $student = User::find($request->student_id);

        if (!$student || !$student->is_student) {
            return response()->json(['error' => 'Invalid student'], 400);
        }

        $clearance = ClearanceRequest::create([
            'student_id' => $student->id,
            'semester' => $request->semester,
            'academic_year' => $request->academic_year,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Clearance created successfully',
            'clearance_id' => $clearance->id
        ]);
    }

    // ===============================
    // STAFF APPROVES SIGNATURE
    // ===============================
    public function approve(Request $request)
    {
        $signature = ClearanceSignature::find($request->signature_id);
        $staff = User::find($request->staff_id);

        if (!$signature || !$staff || !$staff->is_staff) {
            return response()->json(['error' => 'Invalid data'], 400);
        }

        $signature->update([
            'status' => 'approved',
            'signed_by_user_id' => $staff->id,
            'approved_at' => now()
        ]);

        // Check if all signatures approved
        $clearance = $signature->clearanceRequest;

        $remaining = $clearance->signatures()
            ->where('status', '!=', 'approved')
            ->count();

        if ($remaining === 0) {
            $clearance->update(['status' => 'completed']);
        }

        return response()->json([
            'message' => 'Signature approved'
        ]);
    }
}