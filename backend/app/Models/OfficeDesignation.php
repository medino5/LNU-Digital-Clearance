<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeDesignation extends Model
{
    use HasFactory;

    public const TYPE_ACAD_ORG_TREASURER = 'acad_org_treasurer';

    public const TYPE_ACAD_ORG_ADVISER = 'acad_org_adviser';

    public const TYPE_YEAR_LEVEL_TREASURER = 'year_level_treasurer';

    public const TYPE_LIBRARIAN = 'librarian';

    public const TYPE_VPSD = 'vpsd';

    protected $fillable = [
        'key',
        'display_name',
        'office_type',
        'program_id',
        'year_level',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function clearanceSteps()
    {
        return $this->hasMany(ClearanceStep::class);
    }

    public function assignments()
    {
        return $this->hasMany(OfficeDesignationAssignment::class);
    }

    public function activeAssignments()
    {
        return $this->hasMany(OfficeDesignationAssignment::class)
            ->where('is_active', true);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'office_designation_assignments')
            ->withPivot(['assigned_by_user_id', 'assigned_at', 'released_at', 'is_active'])
            ->withTimestamps();
    }

    public function activeUsers()
    {
        return $this->belongsToMany(User::class, 'office_designation_assignments')
            ->wherePivot('is_active', true)
            ->withPivot(['assigned_by_user_id', 'assigned_at', 'released_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_ACAD_ORG_TREASURER => 'Academic Organization Treasurer',
            self::TYPE_ACAD_ORG_ADVISER => 'Academic Organization Adviser',
            self::TYPE_YEAR_LEVEL_TREASURER => 'Year Level Organization Treasurer',
            self::TYPE_LIBRARIAN => 'College Chief Librarian',
            self::TYPE_VPSD => 'Vice President for Student Development',
        ];
    }

    public function officeTypeLabel(): string
    {
        return self::typeOptions()[$this->office_type] ?? $this->office_type;
    }

    public function matchesOfficeAccount(?OfficeAccount $officeAccount): bool
    {
        if (! $officeAccount) {
            return false;
        }

        if ($officeAccount->office_type !== $this->office_type) {
            return false;
        }

        if ((int) $this->program_id !== (int) $officeAccount->program_id) {
            if ($this->program_id || $officeAccount->program_id) {
                return false;
            }
        }

        if ((int) $this->year_level !== (int) $officeAccount->year_level) {
            if ($this->year_level || $officeAccount->year_level) {
                return false;
            }
        }

        return true;
    }

    public function scopeLabel(): ?string
    {
        return match ($this->office_type) {
            self::TYPE_ACAD_ORG_TREASURER, self::TYPE_ACAD_ORG_ADVISER => $this->program?->code,
            self::TYPE_YEAR_LEVEL_TREASURER => match ((int) $this->year_level) {
                1 => '1st Year',
                2 => '2nd Year',
                3 => '3rd Year',
                4 => '4th Year',
                default => $this->year_level ? $this->year_level . 'th Year' : null,
            },
            default => null,
        };
    }

    public function scopeForStudent($query, Student $student)
    {
        return $query->where(function ($officeQuery) use ($student) {
            $officeQuery
                ->where(function ($programScoped) use ($student) {
                    $programScoped
                        ->whereIn('office_type', [
                            self::TYPE_ACAD_ORG_TREASURER,
                            self::TYPE_ACAD_ORG_ADVISER,
                        ])
                        ->where('program_id', $student->program_id);
                })
                ->orWhere(function ($yearScoped) use ($student) {
                    $yearScoped
                        ->where('office_type', self::TYPE_YEAR_LEVEL_TREASURER)
                        ->where('year_level', $student->year_level);
                })
                ->orWhereIn('office_type', [
                    self::TYPE_LIBRARIAN,
                    self::TYPE_VPSD,
                ]);
        });
    }
}
