<?php

namespace App\Models;

use App\Support\StudentNameFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_OFFICE = 'office';

    public const ROLE_STUDENT = 'student';

    protected $fillable = [
        'name',
        'first_name',
        'middle_initial',
        'last_name',
        'name_extension',
        'username',
        'email',
        'password',
        'role',
        'is_student',
        'is_staff',
        'program_id',
        'year_level',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_student' => 'boolean',
        'is_staff' => 'boolean',
    ];

    public function studentProfile()
    {
        return $this->hasOne(Student::class);
    }

    public function officeAccount()
    {
        return $this->hasOne(OfficeAccount::class);
    }

    public function officeDesignationAssignments()
    {
        return $this->hasMany(OfficeDesignationAssignment::class);
    }

    public function officeDesignations()
    {
        return $this->belongsToMany(OfficeDesignation::class, 'office_designation_assignments')
            ->withPivot(['assigned_by_user_id', 'assigned_at', 'released_at', 'is_active'])
            ->withTimestamps();
    }

    public function activeOfficeDesignations()
    {
        return $this->belongsToMany(OfficeDesignation::class, 'office_designation_assignments')
            ->wherePivot('is_active', true)
            ->withPivot(['assigned_by_user_id', 'assigned_at', 'released_at', 'is_active'])
            ->withTimestamps();
    }

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

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isOffice(): bool
    {
        return $this->role === self::ROLE_OFFICE;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function formattedName(): string
    {
        if ($this->isStudent() || $this->is_student) {
            return StudentNameFormatter::compose(
                $this->first_name,
                $this->middle_initial,
                $this->last_name,
                $this->name_extension,
                $this->name,
            );
        }

        return $this->name;
    }

    /**
     * @return array<string, ?string>
     */
    public function studentNameParts(): array
    {
        return [
            'first_name' => StudentNameFormatter::normalizeNamePart($this->first_name),
            'middle_initial' => StudentNameFormatter::normalizeMiddleInitial($this->middle_initial),
            'last_name' => StudentNameFormatter::normalizeNamePart($this->last_name),
            'name_extension' => StudentNameFormatter::normalizeExtension($this->name_extension),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function studentNameExtensionOptions(): array
    {
        return StudentNameFormatter::extensionOptions();
    }
}
