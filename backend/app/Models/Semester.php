<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
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
}
