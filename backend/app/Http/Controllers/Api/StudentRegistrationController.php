<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use App\Support\StudentNameFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StudentRegistrationController extends Controller
{
    public function options()
    {
        return response()->json([
            'programs' => Program::query()
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'org_name'])
                ->map(fn (Program $program) => [
                    'id' => $program->id,
                    'code' => $program->code,
                    'name' => $program->name,
                    'org_name' => $program->org_name,
                ]),
            'year_levels' => collect([1, 2, 3, 4])
                ->map(fn (int $yearLevel) => [
                    'value' => $yearLevel,
                    'label' => $this->yearLevelLabel($yearLevel),
                ]),
            'name_extensions' => User::studentNameExtensionOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'student_id_number' => trim((string) $request->input('student_id_number', '')),
            'first_name' => trim((string) $request->input('first_name', '')),
            'middle_initial' => trim((string) $request->input('middle_initial', '')),
            'last_name' => trim((string) $request->input('last_name', '')),
            'name_extension' => filled($request->input('name_extension'))
                ? trim((string) $request->input('name_extension'))
                : null,
            'email' => filled($request->input('email'))
                ? strtolower(trim((string) $request->input('email')))
                : null,
        ]);

        $data = $request->validate(
            [
                'student_id_number' => $this->studentIdRules(),
                'first_name' => $this->studentNameRules('First name'),
                'middle_initial' => ['nullable', 'string', 'size:1', 'regex:/^\pL$/u'],
                'last_name' => $this->studentNameRules('Last name'),
                'name_extension' => ['nullable', Rule::in(User::studentNameExtensionOptions())],
                'email' => [
                    'nullable',
                    'string',
                    'max:120',
                    'email',
                    'ends_with:@lnu.edu.ph',
                    Rule::unique('users', 'email'),
                ],
                'program_id' => ['required', 'integer', 'exists:programs,id'],
                'year_level' => ['required', 'integer', 'between:1,4'],
                'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
            ],
            [
                'student_id_number.regex' => 'Student ID cannot contain letters or special characters.',
                'student_id_number.size' => 'Student ID must be exactly 7 digits.',
                'middle_initial.regex' => 'Middle initial must be one letter.',
                'email.ends_with' => 'Use your LNU institutional email ending in @lnu.edu.ph.',
                'password.confirmed' => 'Password confirmation does not match.',
            ],
        );

        $nameParts = [
            'first_name' => StudentNameFormatter::normalizeNamePart($data['first_name']) ?? '',
            'middle_initial' => StudentNameFormatter::normalizeMiddleInitial($data['middle_initial'] ?? null),
            'last_name' => StudentNameFormatter::normalizeNamePart($data['last_name']) ?? '',
            'name_extension' => StudentNameFormatter::normalizeExtension($data['name_extension'] ?? null),
        ];

        $student = DB::transaction(function () use ($data, $nameParts) {
            $user = User::create([
                'name' => StudentNameFormatter::compose(
                    $nameParts['first_name'],
                    $nameParts['middle_initial'],
                    $nameParts['last_name'],
                    $nameParts['name_extension'],
                ),
                'first_name' => $nameParts['first_name'],
                'middle_initial' => $nameParts['middle_initial'],
                'last_name' => $nameParts['last_name'],
                'name_extension' => $nameParts['name_extension'],
                'username' => $data['student_id_number'],
                'email' => filled($data['email'] ?? null) ? strtolower($data['email']) : null,
                'password' => Hash::make($data['password']),
                'role' => User::ROLE_STUDENT,
                'is_student' => true,
                'is_staff' => false,
            ]);

            return Student::create([
                'user_id' => $user->id,
                'student_id_number' => $data['student_id_number'],
                'program_id' => $data['program_id'],
                'year_level' => $data['year_level'],
            ])->load(['user', 'program']);
        });

        return response()->json([
            'message' => 'Account created successfully. Please sign in.',
            'student' => [
                'name' => $student->displayName(),
                'student_id_number' => $student->student_id_number,
                'program' => [
                    'id' => $student->program->id,
                    'code' => $student->program->code,
                    'name' => $student->program->name,
                    'org_name' => $student->program->org_name,
                ],
                'year_level' => $student->year_level,
                'year_level_label' => $student->yearLevelLabel(),
            ],
        ], 201);
    }

    /**
     * @return array<int, mixed>
     */
    private function studentIdRules(): array
    {
        return [
            'bail',
            'required',
            'string',
            'regex:/^\d+$/',
            'size:7',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $currentYearPrefix = (int) now()->format('y');
                $enrollmentYearPrefix = (int) substr((string) $value, 0, 2);

                if ($enrollmentYearPrefix > $currentYearPrefix) {
                    $fail('Student ID cannot use a future enrollment year.');
                }
            },
            Rule::unique('students', 'student_id_number'),
            Rule::unique('users', 'username'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private function studentNameRules(string $label): array
    {
        return [
            'required',
            'string',
            'max:60',
            function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
                if (! preg_match("/^\pL[\pL'\\- ]*$/u", (string) $value)) {
                    $fail($label . ' may only contain letters, spaces, apostrophes, and hyphens.');
                }
            },
        ];
    }

    private function yearLevelLabel(int $yearLevel): string
    {
        return match ($yearLevel) {
            1 => '1st Year',
            2 => '2nd Year',
            3 => '3rd Year',
            4 => '4th Year',
            default => $yearLevel . 'th Year',
        };
    }
}
