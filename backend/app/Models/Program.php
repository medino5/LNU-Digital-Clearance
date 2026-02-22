<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function organizations()
    {
        return $this->hasMany(Organization::class);
    }

    public function students()
    {
        return $this->hasMany(User::class);
    }
}