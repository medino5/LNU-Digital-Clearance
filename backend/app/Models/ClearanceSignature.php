<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClearanceSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'clearance_request_id',
        'designation_id',
        'status',
        'signed_by_user_id',
        'remarks',
        'approved_at'
    ];

    public function clearanceRequest()
    {
        return $this->belongsTo(ClearanceRequest::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function signedBy()
    {
        return $this->belongsTo(User::class, 'signed_by_user_id');
    }

    // =========================
    // EXPLICIT APPROVAL LOGIC
    // =========================
    public function approve($staffId)
    {
        // 1. Update this specific signature
        $this->update([
            'status' => 'approved',
            'signed_by_user_id' => $staffId,
            'approved_at' => now()
        ]);

        // 2. Grab the fresh parent clearance request
        $clearance = ClearanceRequest::find($this->clearance_request_id);

        // 3. Count how many signatures are NOT approved yet
        $remaining = ClearanceSignature::where('clearance_request_id', $clearance->id)
            ->where('status', '!=', 'approved')
            ->count();

        // 4. If 0 remaining, mark the whole clearance as completed!
        if ($remaining === 0) {
            $clearance->update(['status' => 'completed']);
        }
    }
}