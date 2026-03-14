<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clearance extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_FLAGGED = 'flagged';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'student_id',
        'semester_id',
        'status',
        'reference_number',
        'completed_at',
        'pdf_path',
        'student_name',
        'student_id_number',
        'year_level',
        'program_code',
        'program_name',
        'organization_name',
        'semester_label',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function steps()
    {
        return $this->hasMany(ClearanceStep::class)->orderBy('id');
    }
}
