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
use Illuminate\Support\Facades\DB;
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
                'code' => 'BSENTREP',
                'name' => 'Bachelor of Science in Entrepreneurship',
                'org_name' => 'Entrep Society',
            ],
            [
                'code' => 'AS',
                'name' => 'Bachelor of Science in Social Work',
                'org_name' => "Junior Social Worker's Association of the Philippines LNU Chapter",
            ],
            [
                'code' => 'EC',
                'name' => 'Bachelor of Early Childhood Education',
                'org_name' => 'Early Childhood Educator Association (ECEO)',
            ],
            [
                'code' => 'SM',
                'name' => 'Bachelor of Secondary Education Major in Mathematics',
                'org_name' => 'Math Student Society',
            ],
        ];

        $this->mergeLegacyProgramCode('BSEntrep', 'BSENTREP', [
            'name' => 'Bachelor of Science in Entrepreneurship',
            'org_name' => 'Entrep Society',
        ]);

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
            [
                'academic_year' => '2024-2025',
                'is_active' => true,
            ]
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
            string $holderName,
            string $officeType,
            ?Program $program = null,
            ?int $yearLevel = null
        ) use ($defaultPassword): void {
            $user = User::updateOrCreate(
                ['username' => $username],
                [
                    'name' => $holderName,
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
                    'display_name' => $holderName,
                    'office_type' => $officeType,
                    'program_id' => $program?->id,
                    'year_level' => $yearLevel,
                ]
            );
        };

        foreach ($programModels as $program) {
            $createOfficeAccount(
                strtolower($program->code) . '.treasurer',
                match ($program->code) {
                    'BSIT' => 'Aira Valdez',
                    'BAEL' => 'Elena Garcia',
                    'BSTM' => 'Marco Rivera',
                    'BSENTREP' => 'Nina Torres',
                    'AS' => 'Rica Manalo',
                    'EC' => 'Hazel Aquino',
                    'SM' => 'Daniel Reyes',
                    default => $program->org_name . ' Treasurer',
                },
                OfficeAccount::TYPE_ACAD_ORG_TREASURER,
                $program
            );

            $createOfficeAccount(
                strtolower($program->code) . '.adviser',
                match ($program->code) {
                    'BSIT' => 'Prof. Ramon Cruz',
                    'BAEL' => 'Prof. Lucia Mendoza',
                    'BSTM' => 'Prof. Celeste Ramos',
                    'BSENTREP' => 'Prof. Joel Mercado',
                    'AS' => 'Prof. Miriam Santiago',
                    'EC' => 'Prof. Arlene Bautista',
                    'SM' => 'Prof. Victor Dizon',
                    default => $program->org_name . ' Adviser',
                },
                OfficeAccount::TYPE_ACAD_ORG_ADVISER,
                $program
            );
        }

        foreach ([1, 2, 3, 4] as $yearLevel) {
            $createOfficeAccount(
                'year' . $yearLevel . '.treasurer',
                match ($yearLevel) {
                    1 => 'Paolo Reyes',
                    2 => 'Trisha Navarro',
                    3 => 'Carlo Santos',
                    4 => 'Mika Lim',
                },
                OfficeAccount::TYPE_YEAR_LEVEL_TREASURER,
                null,
                $yearLevel
            );
        }

        $createOfficeAccount(
            'librarian.office',
            'Lorna Perez',
            OfficeAccount::TYPE_LIBRARIAN
        );

        $createOfficeAccount(
            'vpsd.office',
            'Dean Roberto Cruz',
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
                'section' => '3-1',
                'date_of_birth' => '2005-03-14',
            ]
        );
    }

    /**
     * Merge old mixed-case program seed data into the canonical uppercase code.
     */
    private function mergeLegacyProgramCode(string $legacyCode, string $canonicalCode, array $attributes): void
    {
        DB::transaction(function () use ($legacyCode, $canonicalCode, $attributes): void {
            $legacy = Program::query()->where('code', $legacyCode)->first();

            if (! $legacy) {
                return;
            }

            $canonical = Program::query()->where('code', $canonicalCode)->first();

            if (! $canonical) {
                $legacy->update([
                    'code' => $canonicalCode,
                    ...$attributes,
                ]);

                return;
            }

            DB::table('students')
                ->where('program_id', $legacy->id)
                ->update(['program_id' => $canonical->id]);

            DB::table('office_accounts')
                ->where('program_id', $legacy->id)
                ->update(['program_id' => $canonical->id]);

            DB::table('office_designations')
                ->where('program_id', $legacy->id)
                ->update(['program_id' => $canonical->id]);

            if (DB::getSchemaBuilder()->hasTable('student_registration_requests')) {
                DB::table('student_registration_requests')
                    ->where('program_id', $legacy->id)
                    ->update(['program_id' => $canonical->id]);
            }

            DB::table('clearances')
                ->where('program_code', $legacyCode)
                ->update(['program_code' => $canonicalCode]);

            $canonical->update($attributes + ['code' => $canonicalCode]);
            $legacy->delete();
        });
    }
}
