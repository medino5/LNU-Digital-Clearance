<?php

namespace Database\Seeders;

use App\Models\OfficeAccount;
use App\Models\Program;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Support\OfficeDesignationBackfill;
use App\Support\StudentNameFormatter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CoreSystemSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = Hash::make('password');
        $programs = [
            [
                'code' => 'BSIT',
                'name' => 'Bachelor of Science in Information Technology',
                'org_name' => 'DIGITS',
            ],
            [
                'code' => 'BAEL',
                'name' => 'Bachelor of Arts in English Language',
                'org_name' => 'English Circle',
            ],
            [
                'code' => 'BSTM',
                'name' => 'Bachelor of Science in Tourism Management',
                'org_name' => 'Tourism Circle',
            ],
            [
                'code' => 'BSEntrep',
                'name' => 'Bachelor of Science in Entrepreneurship',
                'org_name' => 'Entrep Society',
            ],
        ];

        $programModels = collect($programs)->mapWithKeys(function (array $program) {
            $model = Program::updateOrCreate(
                ['code' => $program['code']],
                $program
            );

            return [$program['code'] => $model];
        });

        Semester::query()->update(['is_active' => false]);

        Semester::updateOrCreate(
            ['label' => '2nd Semester 2024-2025'],
            ['is_active' => true]
        );

        User::updateOrCreate(
            ['username' => 'mis.admin'],
            [
                'name' => 'MIS Super Admin',
                'email' => null,
                'password' => $defaultPassword,
                'role' => User::ROLE_ADMIN,
                'is_student' => false,
                'is_staff' => false,
            ]
        );

        $createOfficeAccount = function (
            string $username,
            string $displayName,
            string $officeType,
            ?Program $program = null,
            ?int $yearLevel = null
        ) use ($defaultPassword): void {
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $displayName,
                    'email' => null,
                    'password' => $defaultPassword,
                    'role' => User::ROLE_OFFICE,
                    'is_student' => false,
                    'is_staff' => true,
                ]
            );

            OfficeAccount::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'display_name' => $displayName,
                    'office_type' => $officeType,
                    'program_id' => $program?->id,
                    'year_level' => $yearLevel,
                ]
            );
        };

        foreach ($programModels as $program) {
            $createOfficeAccount(
                strtolower($program->code) . '.treasurer',
                $program->org_name . ' Academic Organization Treasurer',
                OfficeAccount::TYPE_ACAD_ORG_TREASURER,
                $program
            );

            $createOfficeAccount(
                strtolower($program->code) . '.adviser',
                $program->org_name . ' Academic Organization Adviser',
                OfficeAccount::TYPE_ACAD_ORG_ADVISER,
                $program
            );
        }

        foreach ([1, 2, 3, 4] as $yearLevel) {
            $createOfficeAccount(
                'year' . $yearLevel . '.treasurer',
                match ($yearLevel) {
                    1 => '1st Year Level Organization Treasurer',
                    2 => '2nd Year Level Organization Treasurer',
                    3 => '3rd Year Level Organization Treasurer',
                    4 => '4th Year Level Organization Treasurer',
                },
                OfficeAccount::TYPE_YEAR_LEVEL_TREASURER,
                null,
                $yearLevel
            );
        }

        $createOfficeAccount(
            'librarian.office',
            'College Chief Librarian',
            OfficeAccount::TYPE_LIBRARIAN
        );

        $createOfficeAccount(
            'vpsd.office',
            'Vice President for Student Development',
            OfficeAccount::TYPE_VPSD
        );

        (new OfficeDesignationBackfill())->run();
        $studentUser = User::updateOrCreate(
            ['username' => '2302314'],
            [
                'name' => StudentNameFormatter::compose('John', 'A', 'Doe', null),
                'first_name' => 'John',
                'middle_initial' => 'A',
                'last_name' => 'Doe',
                'name_extension' => null,
                'email' => null,
                'password' => $defaultPassword,
                'role' => User::ROLE_STUDENT,
                'is_student' => true,
                'is_staff' => false,
            ]
        );

        Student::updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'student_id_number' => '2302314',
                'program_id' => $programModels['BSIT']->id,
                'year_level' => 3,
            ]
        );
    }
}
