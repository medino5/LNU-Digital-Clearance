<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeAccount extends Model
{
    use HasFactory;

    public const SCOPE_PROGRAM = 'program';

    public const SCOPE_YEAR_LEVEL = 'year_level';

    public const SCOPE_GLOBAL = 'global';

    public const TYPE_ACAD_ORG_TREASURER = 'acad_org_treasurer';

    public const TYPE_ACAD_ORG_ADVISER = 'acad_org_adviser';

    public const TYPE_YEAR_LEVEL_TREASURER = 'year_level_treasurer';

    public const TYPE_LIBRARIAN = 'librarian';

    public const TYPE_VPSD = 'vpsd';

    protected $fillable = [
        'user_id',
        'display_name',
        'office_type',
        'program_id',
        'year_level',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function clearanceSteps()
    {
        return $this->hasMany(ClearanceStep::class);
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

    /**
     * @return array<string, array{scope:string, note:string}>
     */
    public static function scopeMetadata(): array
    {
        return [
            self::TYPE_ACAD_ORG_TREASURER => [
                'scope' => self::SCOPE_PROGRAM,
                'note' => 'Requires a program scope. Year level is not used for this designation.',
            ],
            self::TYPE_ACAD_ORG_ADVISER => [
                'scope' => self::SCOPE_PROGRAM,
                'note' => 'Requires a program scope. Year level is not used for this designation.',
            ],
            self::TYPE_YEAR_LEVEL_TREASURER => [
                'scope' => self::SCOPE_YEAR_LEVEL,
                'note' => 'Requires a year level scope. Program scope is not used for this designation.',
            ],
            self::TYPE_LIBRARIAN => [
                'scope' => self::SCOPE_GLOBAL,
                'note' => 'This is a global designation. Leave both program and year level unscoped.',
            ],
            self::TYPE_VPSD => [
                'scope' => self::SCOPE_GLOBAL,
                'note' => 'This is a global designation. Leave both program and year level unscoped.',
            ],
        ];
    }

    public static function scopeTypeForOfficeType(?string $officeType): string
    {
        return self::scopeMetadata()[$officeType]['scope'] ?? self::SCOPE_GLOBAL;
    }

    public static function requiresProgramScopeForType(?string $officeType): bool
    {
        return self::scopeTypeForOfficeType($officeType) === self::SCOPE_PROGRAM;
    }

    public static function requiresYearLevelScopeForType(?string $officeType): bool
    {
        return self::scopeTypeForOfficeType($officeType) === self::SCOPE_YEAR_LEVEL;
    }

    public function requiresProgramScope(): bool
    {
        return self::requiresProgramScopeForType($this->office_type);
    }

    public function requiresYearLevelScope(): bool
    {
        return self::requiresYearLevelScopeForType($this->office_type);
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

    public function officeTypeLabel(): string
    {
        return self::typeOptions()[$this->office_type] ?? $this->office_type;
    }

    public function designationDisplayName(): string
    {
        return match ($this->office_type) {
            self::TYPE_ACAD_ORG_TREASURER => sprintf(
                '%s Academic Organization Treasurer',
                $this->program?->org_name ?? $this->program?->code ?? 'Program'
            ),
            self::TYPE_ACAD_ORG_ADVISER => sprintf(
                '%s Academic Organization Adviser',
                $this->program?->org_name ?? $this->program?->code ?? 'Program'
            ),
            self::TYPE_YEAR_LEVEL_TREASURER => sprintf(
                '%s Level Organization Treasurer',
                $this->yearLevelLabel()
            ),
            self::TYPE_LIBRARIAN => 'College Chief Librarian',
            self::TYPE_VPSD => 'Vice President for Student Development',
            default => $this->officeTypeLabel(),
        };
    }

    public function scopeSummaryLabel(): string
    {
        return match (self::scopeTypeForOfficeType($this->office_type)) {
            self::SCOPE_PROGRAM => 'Program: ' . ($this->program?->code ?? 'Not set'),
            self::SCOPE_YEAR_LEVEL => 'Year Level: ' . $this->yearLevelLabel(),
            default => 'Global: all programs and year levels',
        };
    }

    public function yearLevelLabel(): string
    {
        return match ((int) $this->year_level) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => $this->year_level ? $this->year_level . 'th Year' : 'Not set',
        };
    }
}
