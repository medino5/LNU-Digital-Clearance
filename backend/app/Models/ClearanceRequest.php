<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClearanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'semester',
        'academic_year',
        'status'
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function signatures()
    {
        return $this->hasMany(ClearanceSignature::class);
    }

    // =========================
    // AUTO GENERATE SIGNATURES
    // =========================

    protected static function boot()
    {
        parent::boot();

        static::created(function ($clearance) {

            $student = $clearance->student;

            if (!$student) {
                return;
            }

            // Get organizations of student
            $organizations = $student->organizations;

            foreach ($organizations as $org) {

                foreach ($org->designations as $designation) {

                    ClearanceSignature::create([
                        'clearance_request_id' => $clearance->id,
                        'designation_id' => $designation->id,
                        'status' => 'pending'
                    ]);
                }
            }

        });
    }
}