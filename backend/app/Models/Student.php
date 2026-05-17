<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'student_id_number',
        'program_id',
        'year_level',
        'section',
        'date_of_birth',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function clearances()
    {
        return $this->hasMany(Clearance::class);
    }

    public function displayName(): string
    {
        $this->loadMissing('user');

        return $this->user?->formattedName() ?? '';
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

    public function sectionLabel(): string
    {
        return filled($this->section) ? $this->section : 'No section';
    }
}
