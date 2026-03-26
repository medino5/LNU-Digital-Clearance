<?php

namespace App\Support;

use App\Models\OfficeAccount;
use App\Models\OfficeDesignation;
use App\Models\OfficeDesignationAssignment;
use Illuminate\Support\Str;

class OfficeDesignationBackfill
{
    public function run(): void
    {
        OfficeAccount::query()
            ->with('program')
            ->orderBy('id')
            ->get()
            ->each(fn (OfficeAccount $officeAccount) => $this->syncOfficeAccount($officeAccount));
    }

    public function syncOfficeAccount(OfficeAccount $officeAccount): OfficeDesignation
    {
        $designation = OfficeDesignation::query()->updateOrCreate(
            ['key' => $this->keyForOfficeAccount($officeAccount)],
            [
                'display_name' => $officeAccount->display_name,
                'office_type' => $officeAccount->office_type,
                'program_id' => $officeAccount->program_id,
                'year_level' => $officeAccount->year_level,
                'is_active' => true,
            ]
        );

        $assignment = OfficeDesignationAssignment::query()->firstOrNew([
            'office_designation_id' => $designation->id,
            'user_id' => $officeAccount->user_id,
        ]);

        $assignment->assigned_at = $assignment->assigned_at ?? $officeAccount->created_at ?? now();
        $assignment->released_at = null;
        $assignment->is_active = true;
        $assignment->save();

        return $designation;
    }

    public function keyForOfficeAccount(OfficeAccount $officeAccount): string
    {
        return match ($officeAccount->office_type) {
            OfficeAccount::TYPE_ACAD_ORG_TREASURER => sprintf(
                '%s-acad-org-treasurer',
                $this->programKey($officeAccount)
            ),
            OfficeAccount::TYPE_ACAD_ORG_ADVISER => sprintf(
                '%s-acad-org-adviser',
                $this->programKey($officeAccount)
            ),
            OfficeAccount::TYPE_YEAR_LEVEL_TREASURER => sprintf(
                'year-%d-treasurer',
                max(1, (int) $officeAccount->year_level)
            ),
            OfficeAccount::TYPE_LIBRARIAN => 'college-librarian',
            OfficeAccount::TYPE_VPSD => 'vpsd-office',
            default => Str::slug($officeAccount->display_name ?: $officeAccount->office_type),
        };
    }

    private function programKey(OfficeAccount $officeAccount): string
    {
        $programCode = $officeAccount->program?->code
            ?? ($officeAccount->program_id ? 'program-' . $officeAccount->program_id : 'unscoped');

        return Str::lower($programCode);
    }
}
