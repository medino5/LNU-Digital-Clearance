<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\StudentNameFormatter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UatStudentSeeder extends Seeder
{
    public function run(): void
    {
        $programCodes = ['BSIT', 'BAEL', 'BSTM', 'BSEntrep', 'AS', 'EC', 'SM'];
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

            foreach ([1, 2, 3, 4] as $yearLevel) {
                for ($studentInYear = 1; $studentInYear <= 50; $studentInYear++) {
                    $studentId = sprintf('24%05d', $studentNumber);
                    $nameParts = $this->studentNameParts($studentNumber, $programCode, $yearLevel);
                    $displayName = StudentNameFormatter::compose(
                        $nameParts['first_name'],
                        $nameParts['middle_initial'],
                        $nameParts['last_name'],
                        $nameParts['name_extension'],
                    );

                    $user = User::updateOrCreate(
                        ['username' => $studentId],
                        [
                            'name' => $displayName,
                            'first_name' => $nameParts['first_name'],
                            'middle_initial' => $nameParts['middle_initial'],
                            'last_name' => $nameParts['last_name'],
                            'name_extension' => $nameParts['name_extension'],
                            'email' => null,
                            'password' => $defaultPassword,
                            'role' => User::ROLE_STUDENT,
                            'is_student' => true,
                            'is_staff' => false,
                        ]
                    );

                    Student::updateOrCreate(
                        ['student_id_number' => $studentId],
                        [
                            'user_id' => $user->id,
                            'program_id' => $program->id,
                            'year_level' => $yearLevel,
                        ]
                    );

                    $studentNumber++;
                }
            }
        }
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

        $programOffset = array_search($programCode, ['BSIT', 'BAEL', 'BSTM', 'BSEntrep', 'AS', 'EC', 'SM'], true) ?: 0;

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
}
