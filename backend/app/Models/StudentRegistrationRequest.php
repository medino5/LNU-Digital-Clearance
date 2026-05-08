<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentRegistrationRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'student_id_number',
        'first_name',
        'middle_initial',
        'last_name',
        'name_extension',
        'email',
        'program_id',
        'year_level',
        'password',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
        'created_user_id',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function createdUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_user_id');
    }

    public function displayName(): string
    {
        return trim(collect([
            $this->first_name,
            $this->middle_initial ? $this->middle_initial . '.' : null,
            $this->last_name,
            $this->name_extension,
        ])->filter()->implode(' '));
    }

    public function yearLevelLabel(): string
    {
        return match ((int) $this->year_level) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => $this->year_level . 'th Year',
        };
    }
}
