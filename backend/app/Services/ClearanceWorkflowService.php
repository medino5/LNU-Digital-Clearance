<?php

namespace App\Services;

use App\Models\Clearance;
use App\Models\ClearanceStep;
use App\Models\OfficeDesignation;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ClearanceWorkflowService
{
    public function __construct(
        protected ClearancePdfService $pdfService,
    ) {
    }

    public function activeSemester(): Semester
    {
        $semester = Semester::active()->first();

        if (!$semester) {
            throw new RuntimeException('No active semester is configured.');
        }

        return $semester;
    }

    public function createOrResume(Student $student): Clearance
    {
        $semester = $this->activeSemester();

        $existing = Clearance::with(['steps.officeDesignation.activeUsers', 'steps.events'])
            ->where('student_id', $student->id)
            ->where('semester_id', $semester->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $officeDesignations = $this->resolveOfficeDesignations($student);

        return DB::transaction(function () use ($student, $semester, $officeDesignations) {
            $clearance = Clearance::create([
                'student_id' => $student->id,
                'semester_id' => $semester->id,
                'status' => Clearance::STATUS_IN_PROGRESS,
                'student_name' => $student->displayName(),
                'student_id_number' => $student->student_id_number,
                'year_level' => $student->year_level,
                'program_code' => $student->program->code,
                'program_name' => $student->program->name,
                'organization_name' => $student->program->org_name,
                'semester_label' => $semester->label,
            ]);

            foreach ($officeDesignations as $officeDesignation) {
                $step = $clearance->steps()->create([
                    'office_designation_id' => $officeDesignation->id,
                    'status' => ClearanceStep::STATUS_AWAITING_ACTION,
                    'office_label' => $officeDesignation->display_name,
                    'office_type' => $officeDesignation->office_type,
                    'scope_label' => $officeDesignation->scopeLabel(),
                ]);

                $step->events()->create([
                    'actor_role' => 'system',
                    'action' => 'generated',
                ]);
            }

            return $clearance->load(['steps.officeDesignation.activeUsers', 'steps.events']);
        });
    }

    public function approve(ClearanceStep $step, User $actor, ?string $remarks = null): ClearanceStep
    {
        return $this->transitionStep($step, $actor, ClearanceStep::STATUS_APPROVED, 'approved', $remarks);
    }

    public function flag(ClearanceStep $step, User $actor, ?string $remarks = null): ClearanceStep
    {
        return $this->transitionStep($step, $actor, ClearanceStep::STATUS_FLAGGED, 'flagged', $remarks);
    }

    public function undoApproval(ClearanceStep $step, User $actor): ClearanceStep
    {
        $step->loadMissing('clearance', 'officeDesignation');

        if (! $actor->activeOfficeDesignations()
            ->where('office_designations.id', $step->office_designation_id)
            ->exists()
        ) {
            throw new RuntimeException('You are not allowed to undo this clearance step.');
        }

        if ($step->status !== ClearanceStep::STATUS_APPROVED) {
            throw new RuntimeException('Only approved steps can be undone.');
        }

        DB::transaction(function () use ($step, $actor) {
            $step->update([
                'status' => ClearanceStep::STATUS_AWAITING_ACTION,
                'signed_at' => null,
            ]);

            $step->events()->create([
                'actor_user_id' => $actor->id,
                'actor_role' => $actor->role,
                'action' => 'undo_approval',
            ]);

            $this->syncClearanceStatus($step->clearance->fresh());
        });

        return $step->fresh(['clearance.steps', 'officeDesignation.activeUsers', 'events']);
    }

    public function undoFlag(ClearanceStep $step, User $actor): ClearanceStep
    {
        $step->loadMissing('clearance', 'officeDesignation');

        if (! $actor->activeOfficeDesignations()
            ->where('office_designations.id', $step->office_designation_id)
            ->exists()
        ) {
            throw new RuntimeException('You are not allowed to undo this flagged clearance step.');
        }

        if ($step->status !== ClearanceStep::STATUS_FLAGGED) {
            throw new RuntimeException('Only flagged steps can be undone.');
        }

        DB::transaction(function () use ($step, $actor) {
            $step->update([
                'status' => ClearanceStep::STATUS_AWAITING_ACTION,
                'remarks' => null,
                'signed_at' => null,
            ]);

            $step->events()->create([
                'actor_user_id' => $actor->id,
                'actor_role' => $actor->role,
                'action' => 'undo_flag',
            ]);

            $this->syncClearanceStatus($step->clearance->fresh());
        });

        return $step->fresh(['clearance.steps', 'officeDesignation.activeUsers', 'events']);
    }

    public function resubmit(ClearanceStep $step, Student $student): ClearanceStep
    {
        if ($step->clearance->student_id !== $student->id) {
            throw new RuntimeException('You are not allowed to resubmit this clearance step.');
        }

        if ($step->status !== ClearanceStep::STATUS_FLAGGED) {
            throw new RuntimeException('Only flagged steps can be resubmitted.');
        }

        DB::transaction(function () use ($step, $student) {
            $step->update([
                'status' => ClearanceStep::STATUS_AWAITING_ACTION,
                'signed_at' => null,
            ]);

            $step->events()->create([
                'actor_user_id' => $student->user_id,
                'actor_role' => User::ROLE_STUDENT,
                'action' => 'resubmitted',
            ]);

            $this->syncClearanceStatus($step->clearance->fresh());
        });

        return $step->fresh(['officeDesignation.activeUsers', 'events']);
    }

    public function syncClearanceStatus(Clearance $clearance): Clearance
    {
        $clearance->loadMissing('steps');

        $statuses = $clearance->steps->pluck('status');
        $allApproved = $statuses->isNotEmpty()
            && $statuses->every(fn ($status) => $status === ClearanceStep::STATUS_APPROVED);

        if ($allApproved) {
            if (!$clearance->reference_number) {
                $clearance->reference_number = $this->generateReferenceNumber($clearance);
            }

            $clearance->status = Clearance::STATUS_COMPLETED;
            $clearance->completed_at = now();
            $clearance->save();

            $pdfPath = $this->pdfService->generate($clearance->fresh('steps'));
            $clearance->update(['pdf_path' => $pdfPath]);

            return $clearance->fresh(['steps.officeDesignation.activeUsers', 'steps.events']);
        }

        if ($clearance->pdf_path) {
            Storage::disk('local')->delete($clearance->pdf_path);
        }

        $clearance->update([
            'status' => $statuses->contains(ClearanceStep::STATUS_FLAGGED)
                ? Clearance::STATUS_FLAGGED
                : Clearance::STATUS_IN_PROGRESS,
            'completed_at' => null,
            'reference_number' => null,
            'pdf_path' => null,
        ]);

        return $clearance->fresh(['steps.officeDesignation.activeUsers', 'steps.events']);
    }

    /**
     * @return array<int, OfficeDesignation>
     */
    protected function resolveOfficeDesignations(Student $student): array
    {
        $student->loadMissing('program');

        $programTreasurer = OfficeDesignation::query()
            ->where('is_active', true)
            ->where('office_type', OfficeDesignation::TYPE_ACAD_ORG_TREASURER)
            ->where('program_id', $student->program_id)
            ->first();

        $programAdviser = OfficeDesignation::query()
            ->where('is_active', true)
            ->where('office_type', OfficeDesignation::TYPE_ACAD_ORG_ADVISER)
            ->where('program_id', $student->program_id)
            ->first();

        $yearTreasurer = OfficeDesignation::query()
            ->where('is_active', true)
            ->where('office_type', OfficeDesignation::TYPE_YEAR_LEVEL_TREASURER)
            ->where('year_level', $student->year_level)
            ->first();

        $librarian = OfficeDesignation::query()
            ->where('is_active', true)
            ->where('office_type', OfficeDesignation::TYPE_LIBRARIAN)
            ->first();

        $vpsd = OfficeDesignation::query()
            ->where('is_active', true)
            ->where('office_type', OfficeDesignation::TYPE_VPSD)
            ->first();

        $required = [
            'academic organization treasurer' => $programTreasurer,
            'academic organization adviser' => $programAdviser,
            'year level organization treasurer' => $yearTreasurer,
            'college chief librarian' => $librarian,
            'VPSD' => $vpsd,
        ];

        foreach ($required as $label => $account) {
            if (!$account) {
                throw new RuntimeException('Missing office designation for ' . $label . '.');
            }
        }

        return array_values($required);
    }

    protected function transitionStep(
        ClearanceStep $step,
        User $actor,
        string $status,
        string $action,
        ?string $remarks = null
    ): ClearanceStep {
        $step->loadMissing('clearance', 'officeDesignation');

        if (! $actor->activeOfficeDesignations()
            ->where('office_designations.id', $step->office_designation_id)
            ->exists()
        ) {
            throw new RuntimeException('You are not allowed to process this clearance step.');
        }

        if ($step->status !== ClearanceStep::STATUS_AWAITING_ACTION) {
            throw new RuntimeException('Only awaiting-action steps can be processed.');
        }

        DB::transaction(function () use ($step, $actor, $status, $action, $remarks) {
            $step->update([
                'status' => $status,
                'remarks' => $remarks ? trim($remarks) : null,
                'signed_at' => now(),
            ]);

            $step->events()->create([
                'actor_user_id' => $actor->id,
                'actor_role' => $actor->role,
                'action' => $action,
                'remarks' => $remarks ? trim($remarks) : null,
            ]);

            $this->syncClearanceStatus($step->clearance->fresh());
        });

        return $step->fresh(['clearance.steps', 'officeDesignation.activeUsers', 'events']);
    }

    protected function generateReferenceNumber(Clearance $clearance): string
    {
        do {
            $reference = sprintf(
                'CLR-%d-%s-%04d',
                $clearance->semester_id,
                str_pad((string) $clearance->id, 5, '0', STR_PAD_LEFT),
                random_int(1000, 9999)
            );
        } while (Clearance::where('reference_number', $reference)->exists());

        return $reference;
    }
}
