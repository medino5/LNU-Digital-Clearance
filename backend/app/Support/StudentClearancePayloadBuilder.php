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

        $clearance = $clearance?->loadMissing('steps.events', 'steps.officeDesignation');
        $steps = $clearance?->steps ?? collect();

        return [
            'student' => [
                'name' => $student->displayName(),
                'student_id_number' => $student->student_id_number,
                'year_level' => $student->year_level,
                'year_level_label' => $student->yearLevelLabel(),
                'first_name' => $student->user->first_name,
                'middle_initial' => $student->user->middle_initial,
                'last_name' => $student->user->last_name,
                'name_extension' => $student->user->name_extension,
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
                'counts' => [
                    'total' => $steps->count(),
                    'approved' => $steps->where('status', 'approved')->count(),
                    'flagged' => $steps->where('status', 'flagged')->count(),
                    'awaiting_action' => $steps->where('status', 'awaiting_action')->count(),
                ],
                'steps' => $steps->map(function ($step) {
                    $lastEvent = $step->events->first();

                    return [
                        'id' => $step->id,
                        'status' => $step->status,
                        'remarks' => $step->remarks,
                        'signed_at' => $step->signed_at?->toISOString(),
                        'office_label' => $step->office_label,
                        'office_type' => $step->office_type,
                        'scope_label' => $step->scope_label,
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
}
