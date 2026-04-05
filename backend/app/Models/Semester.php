<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'academic_year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function clearances()
    {
        return $this->hasMany(Clearance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function displayAcademicYear(): ?string
    {
        if ($this->academic_year) {
            return $this->academic_year;
        }

        if (preg_match('/(20\d{2}-20\d{2})$/', $this->label, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
