<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_student',
        'is_staff',
        'program_id',
        'year_level'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_student' => 'boolean',
        'is_staff' => 'boolean',
    ];

    // =========================
    // RELATIONSHIPS
    // =========================

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function designations()
    {
        return $this->belongsToMany(Designation::class);
    }

    public function clearanceRequests()
    {
        return $this->hasMany(ClearanceRequest::class, 'student_id');
    }

    public function signedClearances()
    {
        return $this->hasMany(ClearanceSignature::class, 'signed_by_user_id');
    }
    public function organizations()
    {
        return $this->belongsToMany(
            Organization::class,
            'student_organizations',
            'student_id',
            'organization_id'
        );
    }
}