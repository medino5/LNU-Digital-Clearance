<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\User;
use App\Support\StudentNameFormatter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UatStudentSeeder extends Seeder
{
    private const GENERATED_COUNTS_BY_PROGRAM = [
        'BSIT' => 1142,
        'BAEL' => 1143,
        'BSTM' => 1143,
        'BSENTREP' => 1143,
        'AS' => 1143,
        'EC' => 1143,
        'SM' => 1142,
    ];

    private const UPSERT_CHUNK_SIZE = 500;

    public function run(): void
    {
        $programCodes = array_keys(self::GENERATED_COUNTS_BY_PROGRAM);
        $programs = Program::query()
            ->whereIn('code', $programCodes)
            ->get()
            ->keyBy('code');

        $defaultPassword = Hash::make('password');
        $studentNumber = 1;

        foreach ($programCodes as $programCode) {
            $program = $programs->get($programCode);

            if (!$program) {
                throw new RuntimeException('Missing program for UAT roster: ' . $programCode);
            }

            $userRows = [];
            $studentPayloads = [];

            foreach ($this->yearLevelCounts(self::GENERATED_COUNTS_BY_PROGRAM[$programCode]) as $yearLevel => $yearCount) {
                for ($studentInYear = 1; $studentInYear <= $yearCount; $studentInYear++) {
                    $studentId = sprintf('24%05d', $studentNumber);
                    $nameParts = $this->studentNameParts($studentNumber, $programCode, $yearLevel);
                    $displayName = StudentNameFormatter::compose(
                        $nameParts['first_name'],
                        $nameParts['middle_initial'],
                        $nameParts['last_name'],
                        $nameParts['name_extension'],
                    );
                    $timestamp = now()->toDateTimeString();

                    $userRows[] = [
                        'name' => $displayName,
                        'username' => $studentId,
                        'first_name' => $nameParts['first_name'],
                        'middle_initial' => $nameParts['middle_initial'],
                        'last_name' => $nameParts['last_name'],
                        'name_extension' => $nameParts['name_extension'],
                        'email' => null,
                        'password' => $defaultPassword,
                        'role' => User::ROLE_STUDENT,
                        'is_student' => true,
                        'is_staff' => false,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];

                    $studentPayloads[] = [
                        'student_id_number' => $studentId,
                        'program_id' => $program->id,
                        'year_level' => $yearLevel,
                        'section' => $this->sectionFor($yearLevel, $studentInYear),
                        'date_of_birth' => $this->dateOfBirthFor($studentNumber),
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];

                    $studentNumber++;
                }
            }

            $this->upsertProgramRoster($programCode, $userRows, $studentPayloads);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $userRows
     * @param  array<int, array<string, mixed>>  $studentPayloads
     */
    protected function upsertProgramRoster(string $programCode, array $userRows, array $studentPayloads): void
    {
        $studentIds = array_column($studentPayloads, 'student_id_number');

        foreach (array_chunk($userRows, self::UPSERT_CHUNK_SIZE) as $chunk) {
            DB::table('users')->upsert(
                $chunk,
                ['username'],
                [
                    'name',
                    'first_name',
                    'middle_initial',
                    'last_name',
                    'name_extension',
                    'email',
                    'role',
                    'is_student',
                    'is_staff',
                    'updated_at',
                ]
            );
        }

        $userIds = User::query()
            ->whereIn('username', $studentIds)
            ->pluck('id', 'username');

        $studentRows = array_map(
            static function (array $payload) use ($userIds): array {
                $payload['user_id'] = $userIds[$payload['student_id_number']];

                return $payload;
            },
            $studentPayloads
        );

        foreach (array_chunk($studentRows, self::UPSERT_CHUNK_SIZE) as $chunk) {
            DB::table('students')->upsert(
                $chunk,
                ['student_id_number'],
                [
                    'user_id',
                    'program_id',
                    'year_level',
                    'section',
                    'date_of_birth',
                    'updated_at',
                ]
            );
        }

        $this->command?->info(sprintf('Seeded/updated %s UAT students for %s.', count($studentRows), $programCode));
    }

    /**
     * @return array<int, int>
     */
    protected function yearLevelCounts(int $total): array
    {
        $base = intdiv($total, 4);
        $remainder = $total % 4;
        $counts = [];

        foreach ([1, 2, 3, 4] as $yearLevel) {
            $counts[$yearLevel] = $base + ($yearLevel <= $remainder ? 1 : 0);
        }

        return $counts;
    }

    /**
     * @return array{first_name: string, middle_initial: string, last_name: string, name_extension: ?string}
     */
    protected function studentNameParts(int $studentNumber, string $programCode, int $yearLevel): array
    {
        $firstNames = [
            'Adrian', 'Bianca', 'Carlo', 'Danica', 'Ethan', 'Faith', 'Gabriel', 'Hannah',
            'Ivan', 'Janelle', 'Kevin', 'Lara', 'Miguel', 'Nina', 'Oscar', 'Paula',
            'Rafael', 'Samantha', 'Tristan', 'Vanessa', 'Allen', 'Bea', 'Christian', 'Daphne',
            'Elijah', 'Frances', 'Gian', 'Hazel', 'Ian', 'Joy', 'Kyle', 'Leah',
            'Marco', 'Nadine', 'Paolo', 'Queenie', 'Rico', 'Sophia', 'Therese', 'Wendy',
            'Alden', 'Belle', 'Cedric', 'Denise', 'Enzo', 'Faye', 'Gerard', 'Hope',
            'Jerome', 'Krisha', 'Luis', 'Mara', 'Noel', 'Olivia', 'Patrick', 'Rhea',
            'Sebastian', 'Yssa', 'Anton', 'Camille', 'Diego', 'Ella', 'Franco', 'Grace',
            'Harvey', 'Isabel', 'Joshua', 'Kim', 'Lawrence', 'Mae', 'Nathan', 'Patricia',
            'Renzo', 'Sheena', 'Tyler', 'Vivian', 'Xavier', 'Zara', 'Arvin', 'Clarisse',
            'Dylan', 'Emman', 'Florence', 'Gelo', 'Heidi', 'Jasper', 'Katrina', 'Lester',
            'Marvin', 'Nicole', 'Owen', 'Precious', 'Reynold', 'Shaira', 'Tyrone', 'Veronica',
            'Will', 'Zaine', 'Mikaela', 'Jose Miguel', 'Maria Elise', 'John Carlo', 'Anne Louise',
            'Mark Angelo', 'Christine Joy', 'Juan Miguel', 'Alyssa Mae', 'Sean Patrick',
        ];

        $lastNames = [
            'Abad', 'Bautista', 'Castillo', 'Dela Cruz', 'Espinosa', 'Fernandez', 'Garcia',
            'Hernandez', 'Ignacio', 'Jimenez', 'Lacson', 'Mendoza', 'Navarro', 'Ortega',
            'Pascual', 'Quiambao', 'Ramos', 'Santos', 'Torres', 'Valdez', 'Aquino',
            'Bernardo', 'Cabrera', 'Domingo', 'Evangelista', 'Flores', 'Gonzales', 'Herrera',
            'Ilagan', 'Jose', 'Lopez', 'Mercado', 'Natividad', 'Ocampo', 'Padilla',
            'Quisumbing', 'Rivera', 'Salazar', 'Tan', 'Umali', 'Alcantara', 'Briones',
            'Cunanan', 'De Leon', 'Estrella', 'Fuentes', 'Guerrero', 'Hilario', 'Isidro',
            'Javier', 'Lim', 'Malonzo', 'Noble', 'Olivares', 'Peralta', 'Quinto',
            'Rosales', 'Soriano', 'Trinidad', 'Uy', 'Arceo', 'Balajadia', 'Chua',
            'David', 'Enriquez', 'Ferrer', 'Galang', 'Honrado', 'Infante', 'Jacinto',
            'Luna', 'Manalo', 'Neri', 'Olivar', 'Pineda', 'Querubin', 'Recto',
            'Samson', 'Tolentino', 'Villar', 'Asuncion', 'Bonifacio', 'Catapang',
            'Dizon', 'Elumba', 'Francisco', 'Guinto', 'Hizon', 'Inocencio', 'Joaquin',
            'Labador', 'Maceda', 'Napolis', 'Ong', 'Poblete', 'Quiroz', 'Real',
            'San Diego', 'Teodoro', 'Villanueva', 'Reyes', 'Cruz', 'Sy', 'Co',
            'Lee', 'Tanaka', 'Sullivan', 'Miller', 'Reyes-Chua', 'Dela Pena',
        ];

        $programOffset = array_search($programCode, ['BSIT', 'BAEL', 'BSTM', 'BSENTREP', 'AS', 'EC', 'SM'], true) ?: 0;

        return [
            'first_name' => $firstNames[($studentNumber + ($programOffset * 11)) % count($firstNames)],
            'middle_initial' => chr(65 + (($studentNumber + $yearLevel + $programOffset) % 26)),
            'last_name' => $lastNames[(($studentNumber * 3) + ($programOffset * 17)) % count($lastNames)],
            'name_extension' => match (true) {
                $studentNumber % 211 === 0 => 'III',
                $studentNumber % 137 === 0 => 'Jr',
                default => null,
            },
        ];
    }

    protected function dateOfBirthFor(int $studentNumber): string
    {
        $year = 2001 + ($studentNumber % 8);
        $month = 1 + ($studentNumber % 12);
        $day = 1 + ($studentNumber % 28);

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    protected function sectionFor(int $yearLevel, int $studentInYear): string
    {
        $sectionNumber = (($studentInYear - 1) % 6) + 1;

        return $yearLevel . '-' . $sectionNumber;
    }
}
