<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClearanceStep extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_ACTION = 'awaiting_action';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_FLAGGED = 'flagged';

    protected $fillable = [
        'clearance_id',
        'office_account_id',
        'office_designation_id',
        'status',
        'remarks',
        'signed_at',
        'office_label',
        'office_type',
        'scope_label',
    ];

    protected $casts = [
        'signed_at' => 'datetime',
    ];

    public function clearance()
    {
        return $this->belongsTo(Clearance::class);
    }

    public function officeAccount()
    {
        return $this->belongsTo(OfficeAccount::class);
    }

    public function officeDesignation()
    {
        return $this->belongsTo(OfficeDesignation::class);
    }

    public function events()
    {
        return $this->hasMany(ClearanceStepEvent::class)->latest();
    }
}
