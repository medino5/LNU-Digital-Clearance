<?php

namespace App\Support;

use App\Models\Clearance;

class AdminClearanceDetailBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(Clearance $clearance): array
    {
        $clearance->loadMissing([
            'semester',
            'student.user',
            'student.program',
            'steps.officeAccount.user',
            'steps.officeAccount.program',
            'steps.events.actor',
        ]);

        $steps = $clearance->steps;

        $timeline = $steps
            ->flatMap(function ($step) {
                return $step->events->map(function ($event) use ($step) {
                    $actor = $event->actor;

                    return [
                        'id' => $event->id,
                        'office_label' => $step->office_label,
                        'office_type' => $step->office_type,
                        'action' => $event->action,
                        'remarks' => $event->remarks,
                        'actor_role' => $event->actor_role,
                        'actor' => $actor ? [
                            'id' => $actor->id,
                            'name' => $actor->name,
                            'username' => $actor->username,
                        ] : null,
                        'created_at' => $event->created_at?->toISOString(),
                    ];
                });
            })
            ->sortByDesc(fn (array $event) => $event['created_at'] ?? '')
            ->values()
            ->all();

        return [
            'id' => $clearance->id,
            'status' => $clearance->status,
            'reference_number' => $clearance->reference_number,
            'completed_at' => $clearance->completed_at?->toISOString(),
            'pdf_available' => filled($clearance->pdf_path),
            'student' => [
                'id' => $clearance->student?->id,
                'name' => $clearance->student_name,
                'student_id_number' => $clearance->student_id_number,
                'year_level' => $clearance->year_level,
                'year_level_label' => $clearance->student?->yearLevelLabel(),
                'program' => [
                    'id' => $clearance->student?->program?->id,
                    'code' => $clearance->program_code,
                    'name' => $clearance->program_name,
                    'org_name' => $clearance->organization_name,
                ],
            ],
            'semester' => [
                'id' => $clearance->semester?->id,
                'label' => $clearance->semester_label,
            ],
            'counts' => [
                'total' => $steps->count(),
                'approved' => $steps->where('status', 'approved')->count(),
                'flagged' => $steps->where('status', 'flagged')->count(),
                'awaiting_action' => $steps->where('status', 'awaiting_action')->count(),
            ],
            'steps' => $steps->map(function ($step) {
                $officeAccount = $step->officeAccount;
                $officeUser = $officeAccount?->user;

                return [
                    'id' => $step->id,
                    'status' => $step->status,
                    'remarks' => $step->remarks,
                    'signed_at' => $step->signed_at?->toISOString(),
                    'office_label' => $step->office_label,
                    'office_type' => $step->office_type,
                    'scope_label' => $step->scope_label,
                    'office_account' => $officeAccount ? [
                        'id' => $officeAccount->id,
                        'display_name' => $officeAccount->display_name,
                        'office_type' => $officeAccount->office_type,
                        'office_type_label' => $officeAccount->officeTypeLabel(),
                        'scope_label' => $officeAccount->scopeLabel(),
                        'year_level' => $officeAccount->year_level,
                        'program' => $officeAccount->program ? [
                            'id' => $officeAccount->program->id,
                            'code' => $officeAccount->program->code,
                            'name' => $officeAccount->program->name,
                        ] : null,
                        'user' => $officeUser ? [
                            'id' => $officeUser->id,
                            'name' => $officeUser->name,
                            'username' => $officeUser->username,
                        ] : null,
                    ] : null,
                    'events' => $step->events->map(function ($event) {
                        $actor = $event->actor;

                        return [
                            'id' => $event->id,
                            'action' => $event->action,
                            'remarks' => $event->remarks,
                            'actor_role' => $event->actor_role,
                            'created_at' => $event->created_at?->toISOString(),
                            'actor' => $actor ? [
                                'id' => $actor->id,
                                'name' => $actor->name,
                                'username' => $actor->username,
                            ] : null,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
            'timeline' => $timeline,
        ];
    }
}
