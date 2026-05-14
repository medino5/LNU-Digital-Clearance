<?php

namespace App\Support;

use App\Models\Clearance;
use App\Models\Semester;
use App\Models\Student;

class StudentClearancePayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Student $student, ?Semester $semester, ?Clearance $clearance): array
    {
        $student->loadMissing('user', 'program');

        $clearance = $clearance?->loadMissing('steps.events', 'steps.officeDesignation.activeUsers');
        $steps = $clearance?->steps ?? collect();

        return [
            'student' => [
                'name' => $student->displayName(),
                'student_id_number' => $student->student_id_number,
                'date_of_birth' => $student->date_of_birth?->toDateString(),
                'year_level' => $student->year_level,
                'year_level_label' => $student->yearLevelLabel(),
                'first_name' => $student->user->first_name,
                'middle_initial' => $student->user->middle_initial,
                'last_name' => $student->user->last_name,
                'name_extension' => $student->user->name_extension,
                'profile_photo_url' => $student->user->profilePhotoUrl(),
                'program' => [
                    'id' => $student->program->id,
                    'code' => $student->program->code,
                    'name' => $student->program->name,
                    'org_name' => $student->program->org_name,
                ],
            ],
            'active_semester' => $semester ? [
                'id' => $semester->id,
                'label' => $semester->label,
            ] : null,
            'clearance' => $clearance ? [
                'id' => $clearance->id,
                'status' => $clearance->status,
                'reference_number' => $clearance->reference_number,
                'completed_at' => $clearance->completed_at?->toISOString(),
                'pdf_available' => $clearance->status === Clearance::STATUS_COMPLETED
                    && filled($clearance->pdf_path),
                'can_cancel' => $this->canCancelClearance($clearance, $steps),
                'counts' => [
                    'total' => $steps->count(),
                    'approved' => $steps->where('status', 'approved')->count(),
                    'flagged' => $steps->where('status', 'flagged')->count(),
                    'awaiting_action' => $steps->where('status', 'awaiting_action')->count(),
                ],
                'steps' => $steps->map(function ($step) {
                    $lastEvent = $step->events->first();
                    $assignedOfficer = $step->officeDesignation?->activeUsers?->first();

                    return [
                        'id' => $step->id,
                        'status' => $step->status,
                        'remarks' => $step->remarks,
                        'signed_at' => $step->signed_at?->toISOString(),
                        'office_label' => $step->office_label,
                        'office_type' => $step->office_type,
                        'scope_label' => $step->scope_label,
                        'assigned_officer' => $assignedOfficer ? [
                            'name' => $assignedOfficer->formattedName(),
                            'profile_photo_url' => $assignedOfficer->profilePhotoUrl(),
                        ] : null,
                        'can_resubmit' => $step->status === 'flagged',
                        'last_event' => $lastEvent ? [
                            'action' => $lastEvent->action,
                            'remarks' => $lastEvent->remarks,
                            'created_at' => $lastEvent->created_at?->toISOString(),
                        ] : null,
                    ];
                })->values()->all(),
            ] : null,
        ];
    }

    private function canCancelClearance(Clearance $clearance, $steps): bool
    {
        if ($clearance->status !== Clearance::STATUS_IN_PROGRESS) {
            return false;
        }

        return $steps->every(function ($step) {
            return $step->status === 'awaiting_action'
                && $step->events->every(fn ($event) => $event->action === 'generated');
        });
    }
}
