<?php

namespace Tests;

use App\Models\Clearance;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{
    protected function seededUser(string $username): User
    {
        return User::where('username', $username)->firstOrFail();
    }

    protected function seededAdminUser(): User
    {
        return $this->seededUser('mis.admin');
    }

    protected function seededStudentUser(string $studentId = '2302314'): User
    {
        return $this->seededUser($studentId);
    }

    protected function startClearanceForSeededStudent(string $studentId = '2302314'): Clearance
    {
        Sanctum::actingAs($this->seededStudentUser($studentId));
        $this->postJson('/api/clearance')->assertOk();

        return Clearance::with('steps.officeDesignation.activeUsers')->firstOrFail();
    }

    protected function approveAllClearanceSteps(
        Clearance $clearance,
        string $remarks = 'Approved for test coverage.',
    ): Clearance {
        $clearance->loadMissing('steps.officeDesignation.activeUsers');

        foreach ($clearance->steps as $step) {
            $officeUser = $step->officeDesignation->activeUsers->first();
            $this->assertNotNull($officeUser);

            $this->actingAs($officeUser)
                ->post(route('office.steps.process', $step), [
                    'action' => 'approve',
                    'remarks' => $remarks,
                ])
                ->assertRedirect();
        }

        return $clearance->fresh([
            'semester',
            'student.user',
            'student.program',
            'steps.officeDesignation',
            'steps.events.actor',
        ]);
    }

    protected function createCompletedSeededClearance(
        string $studentId = '2302314',
        string $remarks = 'Approved for test coverage.',
    ): Clearance {
        Storage::disk('local')->deleteDirectory('clearances');

        return $this->approveAllClearanceSteps(
            $this->startClearanceForSeededStudent($studentId),
            $remarks,
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function clearanceSnapshotAttributes(
        Student $student,
        Semester $semester,
        array $overrides = [],
    ): array {
        $student->loadMissing('user', 'program');
        $program = $student->program;

        return array_merge([
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'status' => Clearance::STATUS_IN_PROGRESS,
            'reference_number' => null,
            'completed_at' => null,
            'pdf_path' => null,
            'student_name' => $student->user->formattedName(),
            'student_id_number' => $student->student_id_number,
            'year_level' => $student->year_level,
            'program_code' => $program?->code,
            'program_name' => $program?->name,
            'organization_name' => $program?->org_name,
            'semester_label' => $semester->label,
        ], $overrides);
    }
}
