<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceStepEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'clearance_step_id',
        'actor_user_id',
        'actor_role',
        'action',
        'remarks',
    ];

    public function clearanceStep()
    {
        return $this->belongsTo(ClearanceStep::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
