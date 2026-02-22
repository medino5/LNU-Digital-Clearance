<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'program_id',
        'year_level'
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function designations()
    {
        return $this->hasMany(Designation::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'student_organizations', 'organization_id', 'student_id');
    }
}