<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeDesignationAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_designation_id',
        'user_id',
        'assigned_by_user_id',
        'assigned_at',
        'released_at',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'released_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function officeDesignation()
    {
        return $this->belongsTo(OfficeDesignation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }
}
